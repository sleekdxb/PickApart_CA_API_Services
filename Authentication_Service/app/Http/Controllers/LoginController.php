<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\LoginHelper;
use Illuminate\Support\Facades\Validator;
use App\Models\Account;  // Import the Account model
use App\Services\EncryptionServiceConnections;
use App\Http\Controllers\HeaderValidationController;
class LoginController extends Controller
{
    protected $headerValidationController;

    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    // Handle user login
    public function login(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'acc_d' => 'nullable|string',
            'email' => 'required|email',
            'fcm_token' => 'required|string',  // Basic email validation
            'password' => 'required|string|min:8',  // Basic password validation
        ]);

        // Remove the custom email check logic (previously checking for existing emails)
        // The email validation is now purely based on format and required status

        // If validation fails, return errors

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'code' => 422,  // HTTP status code for Unprocessable Entity
                'data' => $validator->errors()  // Validation errors
            ], 422);
        }

        // Extract the request data

        // Call the login helper to handle authentication and session creation
        return LoginHelper::login($request);
    }


    // Handle user logout


    public function logout(Request $request)
    {
        // Validate the access token


        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'account_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'session_id is required.',
                'code' => 422
            ], 422);
        }

        return LoginHelper::logout($request);
    }


}
