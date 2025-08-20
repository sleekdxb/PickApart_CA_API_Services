<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\AccountNotification;
use Exception;

class AccountNotificationController extends Controller
{
    public function sendAccount(Request $request): JsonResponse
    {
        // 1) Validate with Validator::make to avoid HTML redirects
        $validator = Validator::make($request->all(), [
            'acc_id'        => 'required|string',
            'vend_id'       => 'nullable|string',
            'notifiable_id' => 'required|string',
            'type'          => 'required|string',
            'data'          => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // 2) Create the notification
            $notification = AccountNotification::create([
                'acc_id'        => $request->input('acc_id'),
                'vend_id'       => $request->input('vend_id'),        // optional
                'notifiable_id' => $request->input('notifiable_id'),
                'type'          => $request->input('type'),
                'data'          => json_encode($request->input('data')),
                'read'          => false,
                'read_at'       => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Notification stored successfully.',
                'data'    => $notification,
            ], 200);

        } catch (Exception $e) {
            Log::error('Account notification error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to store the notification.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
