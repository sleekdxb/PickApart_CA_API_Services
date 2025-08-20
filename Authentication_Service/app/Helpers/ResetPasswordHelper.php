<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\SubVendor;
use App\Services\EncryptionServiceConnections;
use App\Services\MailingServiceConnections;
use App\Services\NotificationServiceConnections;
class ResetPasswordHelper
{
     public static function resetPassword(Request $request, $accountData, $subVendorData)
{
    // Hash the password instead of encrypting it
    $hashedPassword = Hash::make($request->input('password'));

    // Check if account data is provided
    if ($accountData !== null) {
        // Update the password for the account
       $account = Account::where('acc_id', $request->acc_id)->first();

if (!$account) {
    return response()->json([
        'status'  => false,
        'message' => 'Account not found.',
    ], 404);
}

// Update password (already hashed) and save
$account->password = $hashedPassword;
 $res=$account->save();

// (Optional) reload fresh values from DB if there are observers/mutators
$account->refresh();

// Decrypt the needed fields from the *model*, not from an int
$account_type_email = EncryptionServiceConnections::decryptData([
    'account_type' => $account->account_type,
    'email'        => $account->email,
]);

$account_type = data_get($account_type_email, 'data.account_type');
$account_email = data_get($account_type_email, 'data.email');
              $mailingDataRestPassword = [
                        'sender_id' => 'SYSTEM',
                        'recipient_id' => $account->acc_id,
                        'email' => $account_email,
                        'name' => $account->firstName . ' ' . $account->lastName,
                        'message' => "Your Pick-a-part.ca password has been changed. If this was done by you, you're all set!",
                        'subject' => 'Pick-a-part.ca – Password Successfully Changed',
                        'upper_info' => 'Password Changed Successfully',
                        'but_info' => 'If you didn’t authorize this change, please reset your password immediately and contact us at support@pick-a-part.ca for assistance.',
                        'data' => '', 
                        'account_type' => $account_type
                    ];

             $notifyDataFiles = [
                'acc_id' => $account->acc_id,
                'type' => 'Password Changed',
                'notifiable_id' => 'SYSTEM',
                'data' => [
                    'message' => 'Your Pick-a-part.ca password was successfully updated. If this wasn’t you, act fast and contact support.',
                    'subject' => 'Password Changed',
                    'name' =>  $account->firstName . ' ' . $account->lastName,
                    'action' => '',
                ]
            ];
      if ($res) {
            MailingServiceConnections::sendEmailResetPasswordParties($mailingDataRestPassword);
            NotificationServiceConnections::notifyAccountParties($notifyDataFiles);
    }

        if ($res) {
            return response()->json([
                'status' => true,
                'message' => 'Password reset is successful.',
                'code' => 200
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Account not found or password update failed.',
            'code' => 400
        ], 400);
    }

    // Check if subVendor data is provided
    if ($subVendorData !== null) {
        // Update the password for the subVendor
        $updateResult = SubVendor::where('sub_ven_id', $request->sub_vend_id)->update(['password' => $hashedPassword]);

        if ($updateResult) {
            return response()->json([
                'status' => true,
                'message' => 'Password reset is successful.',
                'code' => 200
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'SubVendor not found or password update failed.',
            'code' => 400
        ], 400);
    }

    // No data provided
    return response()->json([
        'status' => false,
        'message' => 'No valid data provided for account or subVendor.',
        'code' => 400
    ], 400);
}
}
