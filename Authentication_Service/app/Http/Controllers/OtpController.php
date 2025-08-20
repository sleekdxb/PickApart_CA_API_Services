<?php

namespace App\Http\Controllers;

use App\Helpers\OtpHelper;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Account; // Assuming you have the Account model
use App\Services\EncryptionServiceConnections;
use App\Http\Controllers\HeaderValidationController;
class OtpController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    // Generate OTP for the user
    public function generateOtp(Request $request)
    {
        Log::info($request->action);

        // Validate incoming request to check for acc_id existence and uniqueness
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string|exists:accounts,acc_id', // Validate acc_id exists in Account model
            'email' => 'nullable|string', // Make email optional
            'sub_vend_id' => 'nullable|string',
            'action' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $account = Account::where('acc_id', $request->acc_id)->first();

        // Check if account exists and email is provided
        if (isset($request->email) && $request->email !== null) {
            if ($account) {
                // Decrypt and check if the provided email matches the account email
                $decryptedEmail = EncryptionServiceConnections::decryptData(['email' => $account->email])['data']['email'];
                Log::info($decryptedEmail);
                if ($decryptedEmail !== $request->email) {
                    return response()->json([
                        'status' => false,
                        'code' => 400,
                        'message' => 'The email you entered does not match the one with PickApart or someone else is trying to reset your account',
                    ], 400); // Return a message when the email doesn't match
                }
            } else {
                // Return error if account is not found
                return response()->json([
                    'status' => false,
                    'code' => 404,
                    'message' => 'Account not found.',
                ], 404);
            }
        }

        // Generate OTP after email validation or if email is not provided
        $otpData = OtpHelper::generateOtp($request->acc_id, $request->sub_vend_id, $request->action);

        return response()->json([
            'status' => $otpData['status'],
            'message' => $otpData['message'],
            'data' => [
                'otp_id' => $otpData['otp_id'], // Include otp_id in the response
            ],
        ], $otpData['status'] ? 200 : 500); // Use 200 for success, 500 for failure
    }


    // Verify OTP for the user (unchanged)
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp_id' => 'required|string|exists:otps,otp_id', // Ensure otp_id exists in the OTP table
            'otp' => 'required|string',
            'operation_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // If device_info is provided, decode it and check for required keys


        // Now, you can safely use $device_info array with all required keys.


        $isVerified = OtpHelper::verifyOtp($request->otp_id, $request->otp, $request->device_info, $request->operation_code);
        Log::info('isVerified:', [$isVerified]);
        if ($isVerified) {
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }
    }
}
