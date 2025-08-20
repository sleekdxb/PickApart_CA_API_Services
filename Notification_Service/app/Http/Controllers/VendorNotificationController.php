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
            'data' => [
                'required',
                function ($attr, $value, $fail) {
                    if (is_array($value)) {
                        return;
                    }
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

            // Default push status object
            $push = [
                'attempted' => false,
                'ok' => false,
                'http_code' => null,
                'response' => null,
                'error' => null,
            ];

            // Fetch account & token (PHP 7 compatible)
            $account = Account::where('acc_id', $request->acc_id)->first();
            $fcmToken = $account ? $account->fcm_token : null;

            if (!$fcmToken) {
                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; account has no FCM token',
                    'notification' => $notification,
                    'push' => $push,
                ], 202);
            }

            // Decrypt token
            try {
                $dec = EncryptionServiceConnections::decryptData(['fcm_token' => $fcmToken, 'account_type' => $account->account_type]);
                $decryptedFcmToken = isset($dec['data']['fcm_token']) ? $dec['data']['fcm_token'] : null;
            } catch (Exception $e) {
                $push['attempted'] = true;
                $push['error'] = 'Failed to decrypt FCM token: ' . $e->getMessage();

                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; push not sent (decrypt failed)',
                    'notification' => $notification,
                    'push' => $push,
                ], 207);
            }

            if (!$decryptedFcmToken) {
                $push['attempted'] = true;
                $push['error'] = 'Decrypted token is empty';

                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; push not sent (empty token)',
                    'notification' => $notification,
                    'push' => $push,
                ], 207);
            }

            // Title/body from data or request fallbacks
            $title = (string) ($data['title'] ?? $request->input('title', 'Notification'));
            $body = (string) ($data['body'] ?? $data['subject'] ?? $request->input('body', ''));

            // Send push via helper
            try {
                $result = FCM::sendToToken(
                    $decryptedFcmToken,
                    ['title' => $title, 'body' => $body],

                    // Merge standard metadata with user data (stringify complex values)
                    [
                        'notification_id' => (string) ($notification->id ?? ''),
                        'type' => (string) $notification->type,
                        'acc_id' => (string) $request->acc_id,
                        'vend_id' => (string) $request->vend_id,
                        'account_type' => $dec['data']['account_type']
                    ] + array_map(
                        function ($v) {
                            return is_scalar($v) || $v === null
                                ? (string) $v
                                : json_encode($v, JSON_UNESCAPED_UNICODE);
                        },
                        $data
                    )
                );

                $push = [
                    'attempted' => true,
                    'ok' => isset($result['ok']) ? (bool) $result['ok'] : false,
                    'http_code' => $result['status'] ?? null,
                    'response' => $result['response'] ?? null,
                    'error' => null,
                ];

                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored and push sent',
                    'notification' => $notification,
                    'push' => $push,
                ], 200);

            } catch (Exception $e) {
                $push['attempted'] = true;
                $push['ok'] = false;
                $push['error'] = $e->getMessage();

                return response()->json([
                    'status' => true,
                    'message' => 'Notification stored; push not delivered',
                    'notification' => $notification,
                    'push' => $push,
                ], 207);
            }

        } catch (Exception $e) {
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
