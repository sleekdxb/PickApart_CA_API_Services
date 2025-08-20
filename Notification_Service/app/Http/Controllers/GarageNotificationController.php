<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Helpers\FirebaseMessagingHelper as FCM;
use App\Models\Account;
use App\Models\GarageNotification;
use App\Services\EncryptionServiceConnections;

use Exception;

class GarageNotificationController extends Controller
{
    public function sendGarage(Request $request): JsonResponse
    {
        // Validate request with custom rule
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'gra_id' => 'required|string',
            'type' => 'required|string',
            'notifiable_id' => 'required|string',
            'data' => 'required|array',
        ]);

        // If validation fails, return 422 with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rawData = $request->input('data');
            $data = is_array($rawData) ? $rawData : (json_decode($rawData, true) ?: []);
            // Save to the database
            $notification = GarageNotification::create([
                'acc_id' => $request->acc_id,
                'gra_id' => $request->gra_id,
                'notifiable_id' => $request->notifiable_id,
                'type' => $request->type,
                'data' => json_encode($request->data),
                'read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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

            try {
                $dec = EncryptionServiceConnections::decryptData(['fcm_token' => $fcmToken]);
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

            $title = (string) ($data['title'] ?? $request->input('title', 'Notification'));
            $body = (string) ($data['body'] ?? $data['subject'] ?? $request->input('body', ''));
            try {
                $result = FCM::sendToToken(
                    $decryptedFcmToken,
                    ['title' => $title, 'body' => $body],

                    // Merge standard metadata with user data (stringify complex values)
                    [
                        'notification_id' => (string) ($notification->id ?? ''),
                        'type' => (string) $notification->type,
                        'acc_id' => (string) $request->acc_id,
                        'gra_id' => (string) $request->gra_id,
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
            Log::error('Garage notification error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to store the notification.',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
