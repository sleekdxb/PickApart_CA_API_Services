<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\VendorHelper;
use App\Models\Vendor;

class VendorController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function getVendorProfileById(Request $request)
    {

        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 2: Validate that acc_id exists in the vendors table
        $acc_id = $request->query('acc_id');  // Get acc_id from query parameter (GET request)

        // Step 2: Validate that acc_id is present in the request
        $validator = Validator::make(['acc_id' => $acc_id], [
            'acc_id' => 'required|string', // acc_id is required and must exist in the vendors table
        ]);

        // If validation fails, return a response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }

        // Step 3: Retrieve divides_info from the headers
        $divide_info = $request->header('User-Agent');

        // Step 4: Validate divides_info to make sure it's present and a string
        if (!$divide_info) {
            return response()->json([
                'status' => false,
                'message' => 'divides_info header is required',
                'data' => null
            ], 400); // Bad Request
        }

        // Step 5: Call the getVendorProfile function from VendorHelper
        return VendorHelper::getVendorProfile($request);
    }


    public function setProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }



        // Step 2: Validate that acc_id is present in the request
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string', // acc_id is required and must exist in the vendors table
            'main' => 'nullable|boolean',
        ]);

        // If validation fails, return a response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }

        // Step 3: Retrieve divides_info from the headers
        $divide_info = $request->header('User-Agent');

        // Step 4: Validate divides_info to make sure it's present and a string
        if (!$divide_info) {
            return response()->json([
                'status' => false,
                'message' => 'divides_info header is required',
                'data' => null
            ], 400); // Bad Request
        }

        // Step 5: Call the getVendorProfile function from VendorHelper
        return VendorHelper::setVendorProfile($request);
    }



    public function createProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 2: Validate that acc_id exists in the vendors table

        // Step 2: Validate that acc_id is present in the request
        $validator = Validator::make($request->all(), [
            'main' => 'nullable|boolean',
            'acc_id' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|string',
            'business_name' => 'nullable|string',
            'account_type' => 'nullable|string',
            'location' => 'nullable|string',
            'long' => 'nullable|string',
            'lat' => 'nullable|string',
            'address' => 'nullable|string',
            'country' => 'nullable|string',
            'official_email' => 'nullable|string',
            'official_phone' => 'nullable|string',
            'owner_id_number' => 'nullable|string',
            'owner_id_full_name' => 'nullable|string',
            'state_position' => 'nullable|string',
            'isOwner' => 'nullable|integer',
            'i_admit_not_owner' => 'nullable|integer',
        ]);

        // If validation fails, return a response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }

        // Return response
        return VendorHelper::createProfileById($request); // Call the method on the instance
    }
    //-------------------------------------------------------------------------------


    //-------------------------------------------------------------------------------
    public function updateProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Step 2: Validate that acc_id exists in the vendors table

        // Step 2: Validate that acc_id is present in the request
        $validator = Validator::make($request->all(), [
            'main' => 'nullable|boolean',
            'vend_id' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|string',
            'business_name' => 'nullable|string',
            'account_type' => 'nullable|string',
            'location' => 'nullable|string',
            'long' => 'nullable|string',
            'lat' => 'nullable|string',
            'address' => 'nullable|string',
            'country' => 'nullable|string',
            'official_email' => 'nullable|string',
            'official_phone' => 'nullable|string',
            'owner_id_number' => 'nullable|string',
            'owner_id_full_name' => 'nullable|string',
            'state_position' => 'nullable|string',
            'isOwner' => 'nullable|integer',
            'i_admit_not_owner' => 'nullable|integer',
        ]);

        // If validation fails, return a response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }
        return VendorHelper::updateProfileById($request); // Call the method on the instance
    }




    public function setFileStateById(Request $request)
    {
        // Validation
        $validatedData = Validator::make($request->all(), [
            'acc_id' => 'required|string', // acc_id should be a non-empty string
            'id' => 'required|integer', // ID should be a required integer
            'account' => 'nullable|array', // Account should be an array
            'upload_date' => 'required|array',
            'account.*' => 'string', // Each item in the account array must have a value
            'profile' => 'nullable|array', // Profile should be an array
            'profile.*' => 'string', // Each item in the profile array must have a value
            'file_expiry_data' => 'nullable|array', // Validate upload_protocol as an array
            'file_expiry_data.proof_expiry_date' => 'nullable|string', // Validate 'id' as a string
            'file_expiry_data.tax_expiry_date' => 'nullable|string', // Validate 'type' as a string
            'file_expiry_data.trad_expiry_date' => 'nullable|string', // Validate 'time' as a string
            'file_expiry_data.auth_person_expiry_date' => 'nullable|string',
            'file_expiry_data.em_expiry_date' => 'nullable|string',
            'file_expiry_data.passport_expiry_date' => 'nullable|string',
        ]);

        // No need to check if validation failed manually, Laravel handles that by default.

        // Check if 'account' and 'profile' are in the correct format
        $account = $request->input('account');
        $profile = $request->input('profile');

        if (!is_array($account) || empty($account)) {
            return response()->json([
                'status' => false,
                'message' => 'Account data must be an array with key-value pairs.',
            ], 400);
        }

        if (!is_array($profile) || empty($profile)) {
            return response()->json([
                'status' => false,
                'message' => 'Profile data must be an array with key-value pairs.',
            ], 400);
        }

        // If validation passes, proceed with calling the VendorHelper
        return VendorHelper::setFileStateById($request);
    }


    public function getAccountVendorProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'vend_id' => 'nullable|string'
        ]);

        // If validation fails, return a response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors() // Return the validation errors
            ], 422); // Unprocessable Entity
        }
        // Return response
        return VendorHelper::getAccountVendorProfileById($request); // Call the method on the instance
    }


    public function overview(Request $request)
    {
        return VendorHelper::getPartVendorProfileById($request);
    }

    public function partListing(Request $request)
    {
        return VendorHelper::getPartVendorProfileById($request);
    }



}
