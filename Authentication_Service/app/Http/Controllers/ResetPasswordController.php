<?php
namespace App\Http\Controllers;
use App\Models\Account;
use App\Models\SubVendor;
use App\Models\Otp;
use Validator;
use Illuminate\Http\Request;
use App\Helpers\ResetPasswordHelper;
use Illuminate\Support\Facades\Log;
use App\Services\EncryptionServiceConnections;
class ResetPasswordController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function resetPassword(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'otp_id' => 'required|string',
            'acc_id' => 'nullable|string',
            'sub_vend_id' => 'nullable|string',
            'password' => 'required|string|min:8',  // Basic email validation
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

      
$provided = trim((string) $request->otp_id);

// 1) Try all likely columns, then guard against null
$otpData = Otp::where('otp_id', $provided)   // hashed token case
    ->orWhere('otp', $provided)              // 6-digit code case
    ->orWhere('id', $provided)               // row id case
    ->latest('id')
    ->first();

        // Remove the custom email check logic (previously checking for existing emails)
        // The email validation is now purely based on format and required status
        $accountData = Account::where('acc_id', $request->acc_id)->first();
        Log::info($accountData);
        if ($request->has('acc_id') && $request->acc_id !== null) {

            if (!$accountData) {
                // Decrypt and check if the provided email matches the account email
                return response()->json([
                    'status' => false,
                    'message' => 'This account not found',
                ], 400); // Return a message when the email doesn't match
            }
        }
        $subVendorData = SubVendor::where('sub_ven_id', $request->sub_vend_id)->first();

        if ($request->has('sub_vend_id') && $request->sub_vend_id !== null) {

            if (!$subVendorData) {
                // Decrypt and check if the provided email matches the account email
                return response()->json([
                    'status' => false,
                    'message' => 'This account not found',
                ], 400); // Return a message when the email doesn't match
            }
        }
        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'code' => 422,  // HTTP status code for Unprocessable Entity
                'data' => $validator->errors()  // Validation errors
            ], 422);
        }
        // Call the login helper to handle authentication and session creation
        return ResetPasswordHelper::resetPassword($request, $accountData, $subVendorData);

    }
}