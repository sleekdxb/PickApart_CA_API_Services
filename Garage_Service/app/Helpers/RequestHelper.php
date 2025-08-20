<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Part;
use App\Models\PartName;
use App\Models\Request;
use App\Models\Vendor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request as HttpRequest;
use Carbon\Carbon;
use App\Services\EncryptionServiceConnections;
use App\Services\NotificationServiceConnections;

class RequestHelper
{
    public static function createRequest(HttpRequest $request)
    {
        $senderAccId = $request->input('sender_acc_id');
        $partId = $request->input('part_id');
        $vendId = $request->input('vend_id');

        // Step 1: Check for duplicate request
        if (Request::where('sender_acc_id', $senderAccId)->where('part_id', $partId)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You’ve already requested this part the dealer will be in touch with you soon!',
                'data' => [],
            ], 400);
        }

        // Step 2: Fetch vendor and validate
        $vendor = Vendor::where('vend_id', $vendId)->first();
        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found.',
                'data' => [],
            ], 404);
        }

        // Step 3: Generate and create the request
        $requestId = Str::uuid()->toString();
        $now = Carbon::now();

        Request::create([
            'request_id' => $requestId,
            'part_id' => $partId,
            'vend_id' => $vendId,
            'sender_acc_id' => $senderAccId,
            'vend_acc_id' => $vendor->acc_id,
            'message' => $request->input('message'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Step 4: Notify vendor (optional)
        try {
            $part = Part::where('part_id', $partId)->first();
            $partName = $part ? PartName::where('part_name_id', $part->sub_cat_id)->first() : null;
            $account = Account::where('acc_id', $senderAccId)->first();

            $cleanData = null;

            if ($account) {
                $accountDataToDecrypt = [
                    'email' => $account->email,
                    'phone' => $account->phone,
                ];
                $cleanData = EncryptionServiceConnections::decryptData($accountDataToDecrypt);
            }

            if ($cleanData && isset($cleanData['data'])) {
                $partNameText = $partName ? $partName->name : 'a part';
                $senderFullName = trim(($account->firstName ?? '') . ' ' . ($account->lastName ?? ''));

                NotificationServiceConnections::notify([
                    'acc_id' => $vendor->acc_id,
                    'vend_id' => $vendor->vend_id,
                    'notifiable_id' => $senderAccId,
                    'type' => 'Part Request',
                    'data' => [
                        'message' => "You’ve received a request for {$partNameText} from {$senderFullName}. Phone: {$cleanData['data']['phone']}, Email: {$cleanData['data']['email']}. Tap to view client profile and contacts.",
                        'subject' => 'Part Request Notification',
                        'name' => '',
                        'action' => 'action',
                    ]
                ]);
            }

            $results['sub_results']['notify_result'] = 'sent';
        } catch (\Exception $e) {
            Log::error('Notification send error: ' . $e->getMessage());
            $results['sub_results']['notify_result'] = 'failed';
        }

        // Step 5: Final response
        return response()->json([
            'status' => true,
            'message' => 'Request created successfully.',
            'data' => ['request_id' => $requestId]
        ], 200);
    }
}
