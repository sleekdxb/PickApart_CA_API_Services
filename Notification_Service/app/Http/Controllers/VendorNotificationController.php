<?php

namespace App\Http\Controllers;

use App\Helpers\FirebaseMessagingHelper as FCM;
use App\Models\Account;
use App\Models\VendorNotification;
use App\Services\EncryptionServiceConnections;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\VendorSession;

class VendorNotificationController extends Controller
{
    public function sendVendor(Request $request): JsonResponse
    {
        // Validate input (accept data as array OR JSON string)
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'vend_id' => 'required|string',
            'type' => 'required|string|max:255',
            'notifiable_id' => 'required|string',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Normalize data to array
            $rawData = $request->input('data');
            $data = is_array($rawData) ? $rawData : (json_decode($rawData, true) ?: []);

            // Store notification first
            $notification = VendorNotification::create([
                'acc_id' => $request->acc_id,
                'vend_id' => $request->vend_id,
                'notifiable_id' => $request->notifiable_id,
                'type' => $request->type,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // -----------------------------------------------------------
            // NEW: Fetch all active session tokens for this account
            // -----------------------------------------------------------
            // Expecting a VendorSession model with columns: acc_id, is_active, fcm_token
            $rawTokens = VendorSession::query()
                ->where('acc_id', $request->acc_id)
                ->where('is_active', 1)
                ->pluck('fcm_token')            // [token1, token2, ...]
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

            // Index them as ['fcm1' => token1, 'fcm2' => token2, ...]
            $fcmTokensIndexed = [];
            foreach ($rawTokens as $i => $token) {
                $fcmTokensIndexed['fcm' . ($i + 1)] = $token;
            }

            // -----------------------------------------------------------
            // Decrypt tokens WITHOUT account_type
            // Try bulk first; on failure fall back to per-token
            // -----------------------------------------------------------
            $decryptedTokens = [];
            $decryptErrors = [];

            try {
                $dec = EncryptionServiceConnections::decryptData([
                    'fcm_token' => $fcmTokensIndexed,
                ]);

                // Accept either an associative array return or a single string (edge case)
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
                // Bulk decrypt failed; try per-token
                foreach ($fcmTokensIndexed as $key => $cipher) {
                    try {
                        $res = EncryptionServiceConnections::decryptData([
                            'fcm_token' => [$key => $cipher],
                        ]);
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

            // Title/body from data or request fallbacks
            $title = (string) ($data['title'] ?? $request->input('title', 'Notification'));
            $body = (string) ($data['body'] ?? $data['subject'] ?? $request->input('body', ''));

            // -----------------------------------------------------------
            // Send push to each decrypted token
            // -----------------------------------------------------------
            $push = [];
            $sentCount = 0;

            foreach ($decryptedTokens as $key => $token) {
                try {
                    $result = FCM::sendToToken(
                        $token,
                        ['title' => $title, 'body' => $body],
                        // Merge standard metadata with user data (stringify complex values)
                        [
                            'notification_id' => (string) ($notification->id ?? ''),
                            'type' => (string) $notification->type,
                            'acc_id' => (string) $request->acc_id,
                            'vend_id' => (string) $request->vend_id,
                            'token_key' => (string) $key,
                        ] + array_map(
                            function ($v) {
                                return is_scalar($v) || $v === null
                                    ? (string) $v
                                    : json_encode($v, JSON_UNESCAPED_UNICODE);
                            },
                            $data
                        )
                    );

                    $ok = isset($result['ok']) ? (bool) $result['ok'] : false;
                    if ($ok) {
                        $sentCount++;
                    }

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
            Log::error('sendVendor failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to store/send notification.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
