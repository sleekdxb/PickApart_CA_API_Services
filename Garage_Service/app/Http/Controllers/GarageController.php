<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\GarageHelper;
use App\Models\Garage;

class GarageController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function getProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'gra_id' => 'required|string',
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
        return GarageHelper::createProfileById($request); // Call the method on the instance
    }



    public function createProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            //--------------------------------
            'garage_email' => 'nullable|string',
            'business_phone' => 'nullable|string',
            'garage_name' => 'nullable|string',
            'account_type' => 'nullable|string',
            'location' => 'nullable|string',
            'long' => 'nullable|string',
            'lat' => 'nullable|string',
            'garage_location' => 'nullable|string',
            'country' => 'nullable|string',
            'address' => 'nullable|string',
            'iAgreeToTerms' => 'nullable|integer',

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
        return GarageHelper::createProfileById($request); // Call the method on the instance
    }


    //-----------------------------------update profile-------------------------------------------------------------
    public function updateProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'gra_id' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'garage_email' => 'nullable|string',
            'business_phone' => 'nullable|string',
            'garage_name' => 'nullable|string',
            'account_type' => 'nullable|string',
            'location' => 'nullable|string',
            'long' => 'nullable|string',
            'lat' => 'nullable|string',
            'garage_location' => 'nullable|string',
            'country' => 'nullable|string',
            'address' => 'nullable|string',
            'iAgreeToTerms' => 'nullable|integer',
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
        return GarageHelper::updateProfileById($request); // Call the method on the instance
    }

    //-----------------------------------------------------------------------------------------------

  public function setFileStateById(Request $request)
{
    // Manual validation
    $validator = Validator::make($request->all(), [
        'acc_id' => 'required|string',
        'id' => 'required|string',
        'account' => 'nullable|array',
        'upload_date' => 'required|string',
        'profile' => 'nullable|array',
        'file_expiry_data' => 'required|array',
        'file_expiry_data.registration_certificate_expiry_data' => 'nullable|string',
        'file_expiry_data.proof_of_location_expiry_data' => 'nullable|string',
    ]);

    // Return validation errors if any
    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    // Fallback to empty arrays if null
    $account = $request->input('account') ?? [];
    $profile = $request->input('profile') ?? [];

    // Optional extra array checks
    if (!is_array($account)) {
        return response()->json([
            'status' => false,
            'message' => 'Account must be an array if provided.',
        ], 400);
    }

    if (!is_array($profile)) {
        return response()->json([
            'status' => false,
            'message' => 'Profile must be an array if provided.',
        ], 400);
    }

    // Delegate processing
    return GarageHelper::setFileStateById($request);
}

    public function getAccountGarageProfileById(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'gra_id' => 'nullable|string'
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
        return GarageHelper::getAccountGarageProfileById($request); // Call the method on the instance
    }

}