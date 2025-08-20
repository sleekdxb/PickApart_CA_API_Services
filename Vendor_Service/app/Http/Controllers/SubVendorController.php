<?php

namespace App\Http\Controllers;

use App\Models\SubVendor;
use App\Models\Vendor;  // Import the Vendor model
use App\Models\Account; // Import the Account model
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Validator;
use Carbon\Carbon;
use App\Helpers\SubVendorHelper;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionServiceConnections;
class SubVendorController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function addSubVendorProfile(Request $request): JsonResponse
    {
        // Call the header validation from the HeaderValidationController
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 1: Validate the incoming data in the controller
        // $encryptedEmail = EncryptionServiceConnections::encryptData($request->input('email'));

        // Perform the validation
        $validator = Validator::make($request->all(), [
            'vend_id' => 'required|string',
            'acc_id' => 'required|string',
            'email' => 'required|email', // Don't validate the email uniqueness here
            'job_title' => 'required|string',
            'password' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'access_protocol' => 'required|json', // Assuming access_array is required and should be an array
            'phone' => 'required|string', // Optional field for phone
        ]);

        // Check if the encrypted email already exists in the database
        $existingEmail = SubVendor::where('email', $request->input('email'))->first();

        if ($existingEmail) {
            return response()->json(['status' => false, 'message' => 'This email is already taken.', 'data' => []], 409);
        }

        // Check for validation failure and throw an exception if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }

        // Step 2: Check if vend_id exists in the Vendor model
        $vendor = Vendor::where('vend_id', $request->input('vend_id'))->first();
        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found with the provided vend_id',
            ], 404); // Not Found
        }

        // Step 3: Check if acc_id exists in the Account model

        $account = Account::where('acc_id', $request->input('acc_id'))->first();
        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Account not found with the provided acc_id',
            ], 404); // Not Found
        }

        // Step 4: Return the created SubVendor profile directly in the response
        return SubVendorHelper::addSubVendorProfile($request);
    }


    public function login(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 1: Validate incoming data (email and password)
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Step 2: Check if validation fails and return errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Step 3: Encrypt email before checking the database
        $encryptedEmail = EncryptionServiceConnections::encryptData($request->input('email'));

        // Step 4: Find the subVendor using encrypted email
        $subVendor = SubVendor::where('email', $encryptedEmail)->first();

        // Step 5: Check if no SubVendor is found with this email
        if (!$subVendor) {
            return response()->json([
                'status' => false,
                'message' => 'Email not found.',
            ], 404);
        }

        // Step 6: Check if account is blocked
        $alockState = $subVendor->is_blocked;  // Assuming 'is_blocked' is a boolean field in the SubVendor model

        if ($alockState) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is blocked. Please contact Your SupervisorS.',
            ], 403); // 403 Forbidden response code
        }

        // Step 7: Return response from SubVendorHelper::login
        return SubVendorHelper::login($request, $subVendor);
    }

    //-------------------Update Sub Vendor ----------------------------------------------------------------------------------------------


    public function updateSubVendorProfile(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Perform the validation
        $validator = Validator::make($request->all(), [
            'vend_id' => 'required|string',
            'sub_ven_id' => 'required|string',
            'email' => 'required|email', // Don't validate the email uniqueness here
            'job_title' => 'nullable|string',
            'password' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'update_access_protocol' => 'nullable|json', // Assuming access_array is required and should be an array
            'phone' => 'nullable|string',
            'is_blocked' => 'nullable|boolean'
        ]);


        // Check for validation failure and throw an exception if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }

        // Step 2: Check if vend_id exists in the Vendor model
        $vendor = Vendor::where('vend_id', $request->input('vend_id'))->first();
        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found with the provided vend_id',
            ], 404); // Not Found
        }

        // Step 3: Check if acc_id exists in the Account model

        $subVendor = SubVendor::where('sub_ven_id', $request->input('sub_ven_id'))->first();
        if (!$subVendor) {
            return response()->json([
                'status' => false,
                'message' => 'Account not found with the provided sub_ven_id',
            ], 404); // Not Found
        }

        // Step 4: Return the created SubVendor profile directly in the response
        return SubVendorHelper::updateSubVendorProfile($request);
    }

    //------------------Logout-------------------------------------------------------


    public function logout(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 1: Validate incoming request for token or authentication information
        $validator = Validator::make($request->all(), [
            'sub_ven_id' => 'required|string',
        ]);

        // Step 2: Check if validation fails and return errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Step 3: Encrypt the email before searching for the SubVendor in the database


        // Step 4: Find the SubVendor using the encrypted email
        $subVendor = SubVendor::where('sub_ven_id', $request->input('sub_ven_id'))->first();

        // Step 5: Check if no SubVendor is found with this email
        if (!$subVendor) {
            return response()->json([
                'status' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        // Step 6: Log out the SubVendor by invalidating their session or token
        // Assuming token-based authentication (like JWT) or session-based
        // In case of token-based authentication (e.g., JWT), we could do something like:
        if ($request->bearerToken()) {
            // If using JWT or token-based authentication, revoke token (if applicable)
            // Example for JWT:
            $request->user()->tokens->each(function ($token) {
                $token->delete(); // Deletes all tokens for the user (logout all devices)
            });
        }

        // Step 7: Return response indicating success
        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out.',
        ]);
    }


}
