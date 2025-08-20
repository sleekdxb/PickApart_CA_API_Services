<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\RegistrationHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Account;  // Import the Account model
use App\Services\EncryptionServiceConnections;
use App\Http\Controllers\HeaderValidationController; // Import the new HeaderValidationController

class RegistrationController extends Controller
{
    protected $headerValidationController;

    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }

    public function createAccount(Request $accountData)
    {
        // Call the header validation from the HeaderValidationController
        $headerValidationResponse = $this->headerValidationController->validateHeaders($accountData);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Validate the input data
        $validator = Validator::make($accountData->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('accounts'), // Basic unique rule
            ],
            'phone' => 'required|string|max:20',  // Adding phone validation
            'password' => 'required|string|min:8',
            'account_type' => 'required|string',
            'device_info' => 'nullable|string',
            'fcm_token' => 'required|string'
        ]);

        // Custom validation to check for existing decrypted email
        $validator->after(function ($validator) use ($accountData) {
            $email = $accountData->input('email');
            // Check if any decrypted email in the accounts matches the input email
            $accounts = Account::select('email')->get();  // Select only emails
            foreach ($accounts as $account) {
                $decryptedEmail = EncryptionServiceConnections::decryptData(['email' => $account->email]);

                if ($decryptedEmail && isset($decryptedEmail['data']['email']) && $decryptedEmail['data']['email'] === $email) {
                    $validator->errors()->add('email', 'The email has already been taken.');
                    return;  // Exit loop early if match found
                }
            }
        });

        // If validation fails, return error message for email
        if ($validator->fails()) {
            $errors = $validator->errors();
            $emailErrors = $errors->get('email');
            if (!empty($emailErrors)) {
                return response()->json([
                    'status' => false,
                    'message' => implode(' ', $emailErrors),
                    'data' => []
                ], 422);
            }

            // Return other errors
            return response()->json([
                'status' => false,
                'message' => $errors,
                'data' => []
            ], 422);
        }

        // Call helper to create the account
        return RegistrationHelper::createAccount($accountData);
    }
    
  public function updateFcmToken(Request $request)
{
    // Run header validation first
    $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
    if ($headerValidationResponse) {
        return $headerValidationResponse; // If headers invalid, return immediately
    }

    // Use manual Validator to validate incoming request data
    $validator = Validator::make($request->all(), [
        'acc_id'    => 'required|string|exists:accounts,acc_id',
        'fcm_token' => 'required|string|max:4096',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // Encrypt the FCM token using your encryption service
    $encryptedDataRespond = EncryptionServiceConnections::encryptData([
        'fcm_token' => $request->input('fcm_token')
    ]);
    $fcm_token = $encryptedDataRespond['data']['fcm_token'];

    // Find account by acc_id
    $account = Account::where('acc_id', $request->input('acc_id'))->firstOrFail();

    // Update account with encrypted token
    $account->update([
        'fcm_token' => $fcm_token,
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'FCM token updated successfully',
    ]);
}
}
