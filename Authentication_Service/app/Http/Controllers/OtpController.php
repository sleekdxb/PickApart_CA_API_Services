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
            'otp_id' => 'required|string|exists:otps,otp_id',
            'otp' => 'required|string',
            // restrict to the ops you currently support; add more as needed
            'operation_code' => 'required|string|in:ACTV451EM,ACTVAUTH451EM',
            // session_id is required only for ACTVAUTH451EM
            'session_id' => 'nullable|string|required_if:operation_code,ACTVAUTH451EM',
            'device_info' => 'nullable', // array or JSON string
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Normalize device_info to array
        $deviceInfo = $request->input('device_info');
        if (is_string($deviceInfo)) {
            $decoded = json_decode($deviceInfo, true);
            $deviceInfo = is_array($decoded) ? $decoded : ['raw' => $deviceInfo];
        } elseif (!is_array($deviceInfo)) {
            $deviceInfo = [];
        }

        $result = OtpHelper::verifyOtp(
            $request->otp_id,
            $request->otp,
            $deviceInfo,
            $request->operation_code,
            $request->input('session_id') // may be null unless ACTVAUTH451EM
        );



        if (!($result['ok'] ?? false)) {
            return response()->json([
                'status' => false,
                'message' => $result['message'] ?? 'Invalid or expired OTP',
            ], 400);
        }

        // Build 200 response:
        // - ACTVAUTH451EM: include session_id + token
        // - otherwise: classic shape
        if ($request->operation_code === 'ACTVAUTH451EM') {
            return response()->json([
                'status' => true,
                'message' => $result['message'] ?? 'OTP verified successfully',
                'session_id' => $result['session_id'] ?? null,
                'token' => $result['access_token'] ?? null,
            ], 200);
        }

        // All other ops → old 200
        return response()->json([
            'status' => true,
            'message' => $result['message'] ?? 'OTP verified successfully',
        ], 200);
    }
}
