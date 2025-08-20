<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;
class AuthController extends Controller
{

    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    //--------------------------------------------------------------------------------------------------
    public function addStaff(Request $request)
    {
        // Validate headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Step 1: Basic validation (without checking for uniqueness yet)
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'work_email' => 'required|email',
            'password' => 'required|string',
            'job_title' => 'required|string',
            'phone' => 'required|string',
            'passport_number' => 'required|string',
            'passport_expire_date' => 'required|string',
            'salary' => 'required|string',
            'working_shift' => 'required|string',
            'department' => 'required|string',
            'job_description' => 'nullable|string',
            'shift_start_time' => 'required|string',
            'shift_end_time' => 'required|string',
        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Step 2: Manually check if work_email already exists using select
        $existingEmail = Staff::where('work_email', strtolower($request->input('work_email')))
            ->first();

        if ($existingEmail) {
            return response()->json([
                'status' => false,
                'message' => 'The work email is already in use.',
            ], 409); // 409 Conflict
        }

        // Step 3: Proceed to helper method to add the staff
        return AuthHelper::addStaff($request);
    }


    //--------------------------------------------------------------------------------------------------
    public function resetStaffPassword(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Validate only the staff_id
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,staff_id',
            'new_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Delegate password reset logic
        return AuthHelper::resetStaffPassword($request);
    }



    public function staffLogin(Request $request)
    {
        // Validate headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Basic format validation (without 'exists')
        $validator = Validator::make($request->all(), [
            'work_email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 400);
        }

        // Normalize email to lowercase for consistency
        $email = strtolower($request->input('work_email'));

        // Manually check if email exists using select
        $staff = Staff::select('work_email') // select only necessary fields
            ->where('work_email', $email)
            ->first();

        if (!$staff) {
            return response()->json([
                'status' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        // Proceed to AuthHelper
        return AuthHelper::staffLogin($request);
    }



    //-------------------------------------------------------------------------------------------------------
    public function staffLogout(Request $request)
    {
        // Validate request headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Assuming 'staff' is your custom guard
        $staff = auth('staff')->user();

        if ($staff) {
            $staff->tokens()->delete(); // revoke all tokens
        }

        return response()->json([
            'status' => true,
            'message' => 'Staff logged out successfully.',
        ]);
    }

}
