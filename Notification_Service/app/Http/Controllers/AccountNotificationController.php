<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\AccountNotification;
use App\Models\StrSession;
use App\Services\EncryptionServiceConnections;
use App\Helpers\FirebaseMessagingHelper as FCM;

class AccountNotificationController extends Controller
{
    public function sendAccount(Request $request): JsonResponse
    {
        // 1) Validate (data can be array OR JSON string, like other endpoints)
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'vend_id' => 'nullable|string',
            'notifiable_id' => 'required|string',
            'type' => 'required|string|max:255',
            'data' => [
                'required',
                function ($attr, $value, $fail) {
                    if (is_array($value))
                        return;
                    if (!is_string($value)) {
                        $fail('The data field must be an array or a JSON string.');
                        return;
                    }
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded)) {
                        $fail('The data field must be a valid JSON string.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // 2) Normalize data to array
            $rawData = $request->input('data');
            $data = is_array($rawData) ? $rawData : (json_decode($rawData, true) ?: []);

            // 3) Create the notification
            $notification = AccountNotification::create([
                'acc_id' => $request->input('acc_id'),
                'vend_id' => $request->input('vend_id'), // optional
                'notifiable_id' => $request->input('notifiable_id'),
                'type' => $request->input('type'),
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // -----------------------------------------------------------
            // 4) Collect all active session FCM tokens from StrSession
            // -----------------------------------------------------------
            // Expect StrSession with columns: acc_id, is_active, fcm_token
            $rawTokens = StrSession::query()
                ->where('acc_id', $request->acc_id)
                ->where('is_active', 1)
                ->pluck('fcm_token')
                ->filter(function ($t) {
                    return is_string($t) && trim($t) !== '';
                })
                ->unique()
                ->values()
                ->all();

            if (empty($rawTokens)) {
                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; no active sessions with FCM tokens.',
                    'notification' => $notification,
                    'push' => [],
                ], 202);
            }

            // Index as ['fcm1' => token1, 'fcm2' => token2, ...]
            $fcmTokensIndexed = [];
            foreach ($rawTokens as $i => $token) {
                $fcmTokensIndexed['fcm' . ($i + 1)] = $token;
            }

            // -----------------------------------------------------------
            // 5) Decrypt tokens WITHOUT account_type
            // -----------------------------------------------------------
            $decryptedTokens = [];
            $decryptErrors = [];

            try {
                // Try bulk decrypt first
                $dec = EncryptionServiceConnections::decryptData([
                    'fcm_token' => $fcmTokensIndexed,
                ]);

                $decoded = $dec['data']['fcm_token'] ?? null;
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $val) {
                        if (is_string($val) && trim($val) !== '') {
                            $decryptedTokens[$key] = $val;
                        }
                    }
                } elseif (is_string($decoded) && count($fcmTokensIndexed) === 1) {
                    $decryptedTokens['fcm1'] = $decoded;
                }
            } catch (\Exception $e) {
                // Bulk failed; fall back to per-token decrypt
                foreach ($fcmTokensIndexed as $key => $cipher) {
                    try {
                        $res = EncryptionServiceConnections::decryptData(['fcm_token' => [$key => $cipher]]);
                        $dval = $res['data']['fcm_token'][$key] ?? null;
                        if (is_string($dval) && trim($dval) !== '') {
                            $decryptedTokens[$key] = $dval;
                        } else {
                            $decryptErrors[$key] = 'Empty decrypted token';
                        }
                    } catch (\Exception $ex) {
                        $decryptErrors[$key] = $ex->getMessage();
                    }
                }
            }

            if (empty($decryptedTokens)) {
                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; push not sent (decrypt failed or empty).',
                    'notification' => $notification,
                    'push' => [],
                    'decrypt_errors' => $decryptErrors,
                ], 207);
            }

            // 6) Title/body fallbacks
            $title = (string) ($data['title'] ?? $request->input('title', 'Notification'));
            $body = (string) ($data['body'] ?? $data['subject'] ?? $request->input('body', ''));

            // 7) Send push to each decrypted token
            $push = [];
            $sentCount = 0;

            foreach ($decryptedTokens as $key => $token) {
                try {
                    $meta = [
                        'notification_id' => (string) ($notification->id ?? ''),
                        'type' => (string) $notification->type,
                        'acc_id' => (string) $request->acc_id,
                        'token_key' => (string) $key,
                    ];

                    // include vend_id if present
                    $vendId = $request->input('vend_id');
                    if (is_string($vendId) && trim($vendId) !== '') {
                        $meta['vend_id'] = $vendId;
                    }

                    $result = FCM::sendToToken(
                        $token,
                        ['title' => $title, 'body' => $body],
                        $meta + array_map(
                            function ($v) {
                                return is_scalar($v) || $v === null
                                    ? (string) $v
                                    : json_encode($v, JSON_UNESCAPED_UNICODE);
                            },
                            $data
                        )
                    );

                    $ok = isset($result['ok']) ? (bool) $result['ok'] : false;
                    if ($ok)
                        $sentCount++;

                    $push[$key] = [
                        'attempted' => true,
                        'ok' => $ok,
                        'http_code' => $result['status'] ?? null,
                        'response' => $result['response'] ?? null,
                        'error' => null,
                    ];
                } catch (\Exception $e) {
                    $push[$key] = [
                        'attempted' => true,
                        'ok' => false,
                        'http_code' => null,
                        'response' => null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $message = $sentCount > 0
                ? "Notification stored and push sent to {$sentCount} active session(s)."
                : "Notification stored; push not delivered to any active session.";

            return response()->json([
                'status' => true,
                'message' => $message,
                'notification' => $notification,
                'tokens_submitted' => count($fcmTokensIndexed),
                'tokens_decrypted' => count($decryptedTokens),
                'push' => $push,
                'decrypt_errors' => $decryptErrors,
            ], $sentCount > 0 ? 200 : 207);

        } catch (\Exception $e) {
            Log::error('Account notification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to store/send the notification.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
