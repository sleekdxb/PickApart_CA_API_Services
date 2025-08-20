<?php

namespace App\Helpers;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionServiceConnections;
use App\Models\Account_state;
use App\Models\Account;
use App\Models\SubVendor;
use App\Services\MailingServiceConnections;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendMailingEmail;
class OtpHelper
{
    public static function generateOtp($acc_id, $sub_vend_id, $action)
    {
        // Generate a random 6-digit OTP
        $otp = rand(100000, 999999);

        // Generate a unique OTP ID by hashing the combination of time and OTP
        $otp_id = Hash::make(Carbon::now()->toDateTimeString() . $otp);

        // Prepare the data to store
        $data = [
            'otp_id' => $otp_id,
            'acc_id' => $acc_id,
            'otp' => $otp,
            'is_used' => 0,
            'expires_at' => Carbon::now()->addMinute()->addSeconds(30),
        ];

        // Only add 'sub_vend_id' if it is not empty
        if (!empty($sub_vend_id)) {
            $data['sub_vend_id'] = $sub_vend_id;
        }

        // Store OTP in the database
        Otp::create($data);


        // Retrieve the account details based on acc_id
        $account = Account::where('acc_id', $acc_id)->first();

        if ($account) {
            $email = $account->email;
        }

        $subVendor = SubVendor::where('sub_ven_id', $sub_vend_id)->first();

        if ($subVendor) {
            $email = $subVendor->email;
        }

        // Decrypt the account's email address
        $decryptedEmail = EncryptionServiceConnections::decryptData([
            'email' => $email,
        ]);

        if ($decryptedEmail && isset($decryptedEmail['data']['email'])) {
            // Prepare the email data
            if ($account) {
                $mailingDataVerifyAccount = [
                    'sender_id' => $acc_id,
                    'recipient_id' => $acc_id,
                    'email' => $decryptedEmail['data']['email'],
                    'name' => $account->firstName . ' ' . $account->lastName,
                    'message' => 'Thank you for creating your PickaPart.ae account. Please enter this code to verify your email address.',
                    'subject' => 'Verify Your Email Address',
                    'data' => array_map('intval', str_split($otp)), // Convert OTP to array of integers
                ];
            }
            if ($decryptedEmail && isset($decryptedEmail['data']['email'])) {
                if ($account) {
                    $mailingDataRestPasswordAccount = [
                        'sender_id' => $acc_id,
                        'recipient_id' => $acc_id,
                        'email' => $decryptedEmail['data']['email'],
                        'name' => $account->firstName . ' ' . $account->lastName,
                        'message' => "You have submitted a password change request. If it wasn't you, please disregard this email and make sure you can still log into your account. If it was you, then click the button below to change your password",
                        'subject' => 'Change your Password',
                        'data' => array_map('intval', str_split($otp)), // Convert OTP to array of integers
                    ];
                }
            }

            if ($subVendor) {
                $mailingDataRestPasswordSubVendor = [
                    'sender_id' => $sub_vend_id,
                    'recipient_id' => $sub_vend_id,
                    'email' => $decryptedEmail['data']['email'],
                    'name' => $subVendor->first_name . ' ' . $subVendor->last_name,
                    'message' => "You have submitted a password change request. If it wasn't you, please disregard this email and make sure you can still log into your account. If it was you, then click the button below to change your password",
                    'subject' => 'Change your Password',
                    'data' => array_map('intval', str_split($otp)), // Convert OTP to array of integers
                ];
            }

            try {
                // Attempt to send the email using the MailingServiceConnections
                // $mailingRespond = MailingServiceConnections::sendEmail($mailingData);
                if ($action == 'ACTV451EM' && $account) {
                    SendMailingEmail::dispatch($mailingDataVerifyAccount);
                }

                if ($action == 'RPASS451EMA' && $account) {
                    SendMailingEmail::dispatch($mailingDataRestPasswordAccount);
                }

                if ($action == 'RPASS451EMSV' && $subVendor) {
                    SendMailingEmail::dispatch($mailingDataRestPasswordSubVendor);
                }


                // If successful, return a success response with otp_id
                return [
                    'status' => true,
                    'message' => 'An OTP has been sent to your email address for verification. Please check your inbox and enter the OTP to verify your email.',
                    'otp_id' => $otp_id, // Include otp_id in the response
                ];
            } catch (\Exception $e) {
                // Handle any exceptions that occur during the email sending process
                return [
                    'status' => false,
                    'message' => 'Failed to send email. Error: ' . $e->getMessage(),
                    'otp_id' => null, // Return null for otp_id on error
                ];
            }
        }

        // If the email decryption failed, return a failure response
        return [
            'status' => false,
            'message' => 'Failed to retrieve the email address.',
            'otp_id' => null, // Return null for otp_id on error
        ];
    }

    // Function to verify OTP
    public static function verifyOtp($otp_id, $otp, $device_info, $operation_code)
    {
        if ($operation_code === 'RPASS451EMA') {
            // Fetch the OTP record based on otp_id, otp, and is_used = 0
            $otpRecord = Otp::where('otp_id', $otp_id)
                ->where('otp', $otp)
                ->where('is_used', 0)
                ->first();



            // If no OTP record is found or the OTP is expired, return false
            if (!$otpRecord) {
                Log::warning('OTP record not found or has already been used.');
                return false;
            }

            // Ensure the OTP is not expired
            if (Carbon::now()->gt($otpRecord->expires_at)) {
                Log::warning('OTP has expired.', ['expires_at' => $otpRecord->expires_at]);
                return false;  // OTP expired
            }
        }

        if ($operation_code === 'RPASS451EMSV') {
            // Fetch the OTP record based on otp_id, otp, and is_used = 0
            $otpRecord = Otp::where('otp_id', $otp_id)
                ->where('otp', $otp)
                ->where('is_used', 0)
                ->first();



            // If no OTP record is found or the OTP is expired, return false
            if (!$otpRecord) {
                Log::warning('OTP record not found or has already been used.');
                return false;
            }

            // Ensure the OTP is not expired
            if (Carbon::now()->gt($otpRecord->expires_at)) {
                Log::warning('OTP has expired.', ['expires_at' => $otpRecord->expires_at]);
                return false;  // OTP expired
            }
        }


        // Get the acc_id from the OTP record


        // Check if operation_code is 'ACTV451EM'
        if ($operation_code === 'ACTV451EM') {
            $otpRecord = Otp::where('otp_id', $otp_id)
                ->where('otp', $otp)
                ->where('is_used', 0)
                ->first();

            // If no OTP record is found or the OTP is expired, return false
            if (!$otpRecord) {
                Log::warning('OTP record not found or has already been used.');
                return false;
            }

            // Ensure the OTP is not expired
            if (Carbon::now()->gt($otpRecord->expires_at)) {
                Log::warning('OTP has expired.', ['expires_at' => $otpRecord->expires_at]);
                return false;  // OTP expired
            }

            // Get the acc_id from the OTP record
            $accountId = $otpRecord->acc_id;
            // Fetch the account using the acc_id
            $account = Account::where('acc_id', $accountId)->first();

            if (!$account) {

                return false; // If the account is not found, return false
            }

            // Prepare data to encrypt
            $dataToEncrypt = [
                'state_code' => 'SYSV4512', // state_code
                'state_name' => 'Verified', // state_name
                'note' => 'Account email is verified.', // note
                'reason' => 'Account email verification.' // reason
            ];

            // Encrypt the data using EncryptionServiceConnections::encryptData
            try {
                $encryptedDataRespond = EncryptionServiceConnections::encryptData($dataToEncrypt);

                if (!$encryptedDataRespond['status']) {
                    // Log the error details for troubleshooting
                    Log::error('Encryption failed: ', [
                        'message' => $encryptedDataRespond['message'],
                        'data' => $dataToEncrypt
                    ]);

                    // If encryption fails, return an error response
                    return response()->json([
                        'status' => false,
                        'message' => $encryptedDataRespond['message'],
                        'code' => $encryptedDataRespond['code'],
                        'data' => null
                    ], $encryptedDataRespond['code']);
                }
            } catch (\Exception $e) {
                // Log the exception details for troubleshooting
                Log::error('Exception during encryption: ', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $dataToEncrypt
                ]);

                // Return a response with the exception message
                return response()->json([
                    'status' => false,
                    'message' => 'An unexpected error occurred during encryption.',
                    'code' => 500,
                    'data' => null
                ], 500);
            }

            $finalEncryptedData = $encryptedDataRespond['data'];
            // Extract encrypted values
            $encryptedStateCode = $finalEncryptedData['state_code'];
            $encryptedStateName = $finalEncryptedData['state_name'];
            $encryptedNote = $finalEncryptedData['note'];
            $encryptedReason = $finalEncryptedData['reason'];

            // Create the account state
            $accountState = Account_state::create([
                'state_id' => Hash::make(Carbon::now()),  // Generate unique state_id
                'acc_id' => $account->acc_id,  // Linking the account state to the account
                'doer_acc_id' => $account->acc_id, // Assuming this is the account performing the action
                'state_code' => $encryptedStateCode,
                'state_name' => $encryptedStateName,
                'note' => $encryptedNote,
                'reason' => $encryptedReason,
                'time_period' => Carbon::now()->addYears(99), // Lifetime period
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Update the account with the new system_state_id
            $account->update([
                'system_state_id' => $accountState->state_id,  // Set system_state_id
            ]);
        }

        // Mark the OTP as used to prevent further use
        $otpRecord->is_used = 1;
        $otpRecord->save();

        // Return true to indicate successful OTP verification
        return true;
    }

}
