<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Account_state;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Services\VendorServiceConnections;
use App\Services\EncryptionServiceConnections;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Jobs\GenerateOtpJob;
use App\Models\CacheItem;
use App\Jobs\SetVendorProfileJob;
use App\Services\MailingServiceConnections;
use Illuminate\Support\Str;
use App\Models\AccountNotifictionschannle;
use App\Models\GarageNotifictionsChannle;
use InvalidArgumentException;
class RegistrationHelper
{
    public static function createAccount(Request $request)
    {
        // Prepare the data for encryption (only the fields to be encrypted)
        $dataToEncrypt = [
            'email' => $request->input('email'),
            'fcm_token' => $request->input('fcm_token'),
            'phone' => $request->input('phone'),
            'account_type' => $request->input('account_type'),
            'state_code' => 'SYSUV4512', // 'Unverified' state code
            'state_name' => 'Unverified', // State name
            'note' => 'Account created but email unverified.',
            'reason' => 'Account email is pending verification.',
            'time_period' => Carbon::now()->addMonths(3), // The time period for 3 months
        ];

        // Call the microservice for encryption
        try {

            $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);



            $encryptedData = $encryptedDataRespond['data'];
            // Ensure encryptedData has all the necessary fields
            $encryptedEmail = $encryptedData['email'] ?? $dataToEncrypt['email'];
            $encryptedPhone = $encryptedData['phone'] ?? $dataToEncrypt['phone'];
            $encryptedAccountType = $encryptedData['account_type'] ?? $dataToEncrypt['account_type'];
            $encryptedStateCode = $encryptedData['state_code'] ?? $dataToEncrypt['state_code'];
            $encryptedStateName = $encryptedData['state_name'] ?? $dataToEncrypt['state_name'];
            $encryptedNote = $encryptedData['note'] ?? $dataToEncrypt['note'];
            $encryptedReason = $encryptedData['reason'] ?? $dataToEncrypt['reason'];
            $encryptedFcm_token = $encryptedData['fcm_token'] ?? $dataToEncrypt['fcm_token'];

            // Create the Account record
            DB::beginTransaction();

            // Create the account (no encryption needed here for the fields other than email/phone)
            $account = Account::create([
                'acc_id' => Hash::make($request->input('email')),  // Using the hashed email for acc_id
                'firstName' => $request->input('firstName'),
                'lastName' => $request->input('lastName'),
                'email' => $encryptedEmail,
                'phone' => $encryptedPhone,
                'fcm_token' => $encryptedFcm_token ?? '',
                'account_type' => $encryptedAccountType,
                'password' => Hash::make($request->input('password')),  // Hashing the password
                'created_at' => now(),  // Setting created_at to current time
                'updated_at' => now(),  // Setting updated_at to current time
            ]);

            // Create an initial AccountState with encrypted values
            $accountState = Account_state::create([
                'state_id' => Hash::make(now()),  // Create a unique state_id
                'acc_id' => $account->acc_id,  // Linking the account state to the created account
                'doer_acc_id' => $account->acc_id, // Optional: set doer_acc_id from request (e.g., admin user)
                'state_code' => $encryptedStateCode, // Encrypted state_code
                'state_name' => $encryptedStateName, // Encrypted state_name
                'note' => $encryptedNote,  // Encrypted note
                'reason' => $encryptedReason,  // Encrypted reason
                'time_period' => Carbon::now()->addMonths(3), // Encrypted time_period, make sure it's correctly formatted
                'created_at' => now(),  // Setting created_at to current time
                'updated_at' => now(),  // Setting updated_at to current time
            ]);

            // Update the Account with the state_id from Account_state
            $account->update([
                'system_state_id' => $accountState->state_id,  // Set system_state_id to the state_id from Account_state
            ]);



            // $otpCallJob = GenerateOtpJob::dispatch($account->acc_id, $request->input('device_info'));

            // SetVendorProfileJob::dispatch($account->acc_id, true);

            $otpResponseDataFromCache = OtpHelper::generateOtp($account->acc_id, '', 'ACTV451EM');



            DB::commit();
            // Retrieve the AccountState and decrypt its fields
            $accountState = Account_state::where('state_id', $account->system_state_id)->first();

            // Decrypt the data using the EncryptionServiceConnections
            $dataToDecrypt = [
                'state_code' => $accountState->state_code,
                'state_name' => $accountState->state_name
            ];

            $decryptedDataRespond = EncryptionServiceConnections::decryptData($dataToDecrypt);

            // VendorServiceConnections::setVendorProfile($account->acc_id, true);


            self::createNotificationChannel($account->acc_id, $request->input('account_type'));
            $decryptedData = $decryptedDataRespond['data'];
            $mailingData = [
                'sender_id' => 'SYSTEM',
                'recipient_id' => $account->acc_id,
                'email' => $request->input('email'),
                'name' => $request->input('firstName') . '' . $request->input('lastName'),
                'message' => 'You can now explore verified auto parts, connect with vendors, and enjoy a safe and convenient experience.',
                'subject' => 'Welcome to Pick-a-part.ca!',
                'data' => ''
            ];

            MailingServiceConnections::sendEmailRgestration($mailingData);

            // Return a success response with decrypted data
            return response()->json([
                'status' => true,
                'message' => 'Account created successfully.',
                'code' => 200,
                'data' => [
                    'state' => $decryptedData,
                    'acc_id' => $account->acc_id,
                    'otp_id' => $otpResponseDataFromCache['otp_id'] //isset($otpResponse['otp_id']) ? $otpResponse['otp_id'] : [],
                ]  // Include the decrypted state information
            ], 200);



        } catch (\Exception $e) {
            DB::rollBack();

            // Log the exception details for troubleshooting
            Log::error('Registration failed: ', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            // Return an error response in case of exception
            return response()->json([
                'status' => false,
                'message' => 'Error during registration: ' . $e->getMessage(),
                'code' => 500,  // HTTP status code for Internal Server Error
                'data' => null  // You can optionally include null or more details
            ], 500);
        }
    }

    public static function createNotificationChannel(string|int $accId, string $accountType, array $extraData = [])
    {
        $type = strtoupper(trim($accountType));

        // Pick the model based on type
        $modelClass = match ($type) {
            'STR' => AccountNotifictionschannle::class,
            'Garage' => GarageNotifictionsChannle::class,
            default => throw new InvalidArgumentException("Unsupported account_type: {$accountType}"),
        };

        // If STR, check if a channel already exists for this acc_id
        if ($type === 'STR') {
            $existing = $modelClass::where('acc_id', $accId)->first();
            if ($existing) {
                return $existing; // Return the existing record without creating
            }
        }

        // Generate a unique 7-digit channel_frequency
        do {
            $frequency = (string) random_int(1000000, 9999999);
        } while ($modelClass::where('channel_frequency', $frequency)->exists());

        // Generate a unique channel_name based on type + random characters (e.g., STR-AB12CD)
        do {
            $channelName = $type . '-' . Str::upper(Str::random(6));
        } while ($modelClass::where('channel_name', $channelName)->exists());

        // Create the record
        $now = Carbon::now();
        $record = $modelClass::create([
            'channel_name' => $channelName,
            'acc_id' => (string) $accId,  // use acc_id
            'channel_frequency' => $frequency,
            'latest_data' => null,             // force null
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $record->fresh();
    }
}
