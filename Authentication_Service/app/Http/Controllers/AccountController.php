<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\EncryptionServiceConnections;
use App\Services\MailingServiceConnections;
use App\Services\NotificationServiceConnections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function updateAccount(Request $request)
{
    // 1. Validate input
    $validator = Validator::make($request->all(), [
        'acc_id' => 'required|string|exists:accounts,acc_id',
        'firstName' => 'nullable|string',
        'lastName' => 'nullable|string',
        'phone' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error.',
            'errors' => $validator->errors(),
        ], 422);
    }

    // 2. Retrieve the account
    $account = Account::where('acc_id', $request->acc_id)->first();
    if (!$account) {
        return response()->json([
            'status' => false,
            'message' => 'Account not found.',
        ], 404);
    }

            $account_type_email = EncryptionServiceConnections::decryptData(['account_type' => $account->account_type,'email' => $account->email]);
            $account_type = $account_type_email['data']['account_type'];
            $account_email = $account_type_email['data']['email'];

    // 3. Prepare update payload
    $updateData = [];

    if ($request->filled('firstName')) {
        $updateData['firstName'] = $request->firstName;
    }

    if ($request->filled('lastName')) {
        $updateData['lastName'] = $request->lastName;  // Fixed the typo
    }

    if ($request->filled('phone')) {
        $encryptedPhone = EncryptionServiceConnections::encryptData(
            ['phone' => $request->phone]
        )['data']['phone'];
        $updateData['phone'] = $encryptedPhone;
    }

    // Return early if nothing to update
    if (empty($updateData)) {
        return response()->json([
            'status' => false,
            'message' => 'No data provided to update.',
        ], 400);
    }

    // 4. Perform the update
    $updateSuccess = $account->update($updateData);  // This will return true/false

    if (!$updateSuccess) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to update the account.',
        ], 500);
    }

    // 5. Notify if updated successfully
    if ($updateSuccess) {
        // Determine name using updated values if present, or fallback to existing
        $fullName =
            ($request->filled('firstName') ? $request->firstName : $account->firstName)
            . ' ' .
            ($request->filled('lastName') ? $request->lastName : $account->lastName);

        $mailingData = [
            'sender_id' => 'SYSTEM',
            'recipient_id' => $account->acc_id,
            'email' => $account_email,
            'name' => $fullName,
            'message' => 'Your Pick-a-part.ca Account was recently updated. If you made these changes, no further action is required.',
            'subject' => 'Pick-a-part.ca Your Account Information Was Updated',
            'upper_info' => 'Account Update Notice',
            'but_info' => 'If you didn’t update your Account, please review your account activity and contact us immediately at support@pick-a-part.ca.',
            'data' => '',
            'account_type' =>  $account_type
        ];

        MailingServiceConnections::sendEmailAccount($mailingData);

        $notifyDataFiles = [
            'acc_id' => $account->acc_id,
            'notifiable_id' => 'SYSTEM',
            'type' => 'Account Update',
            'data' => [
                'message' => 'Your Pick-a-part.ca Account was edited. If this wasn’t you, please contact support right away.',
                'subject' => 'Pick-a-part.ca Your Account Information Was Updated',
                'name' => $fullName,
                'action' => '',
            ]
        ];

         NotificationServiceConnections::notifyAccountParties($notifyDataFiles);
    }

    return response()->json([
        'status' => true,
        'message' => 'Account updated successfully.',
        'data' => [
            'acc_id' => $account->acc_id,
        ],
    ]);
}

}
