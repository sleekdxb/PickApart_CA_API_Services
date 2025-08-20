<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\VendorManageHelper;

class VendorManageController extends Controller
{

    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;


    }
    public function UpdateVendorWithAccount(Request $request)
    {
        // Validate headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Step 1: Basic validation (without checking for uniqueness yet)
        $validator = Validator::make($request->all(), [
            // Account
            'staff_id' => 'required|string',
            'account' => 'required|array',
            'account.acc_id' => 'required|string',
            'account.email' => 'nullable|string|email',
            'account.password' => 'nullable|string|min:6',
            'account.phone' => 'nullable|string',
            'account.account_type' => 'nullable|string',
            'account.firstName' => 'nullable|string',
            'account.lastName' => 'nullable|string',

            // Vendor
            'vendor' => 'required|array',
            'vendor.vend_id' => 'required|string',
            'vendor.business_name' => 'nullable|string',
            'vendor.address' => 'nullable|string',
            'vendor.country' => 'nullable|string',
            'vendor.official_email' => 'nullable|string|email',
            'vendor.official_phone' => 'nullable|string',
            'vendor.owner_id_number' => 'nullable|string',
            'vendor.owner_id_full_name' => 'nullable|string',

            // Vendor State
            'vendor_state' => 'nullable|array',
            'vendor_state.note' => 'nullable|string',
            'vendor_state.reason' => 'nullable|string',
            'vendor_state.state_code' => 'nullable|string',
            'vendor_state.state_name' => 'nullable|string',

            // File State
            'file_state' => 'nullable|array',
            'file_state.*.acc_media_id' => 'nullable|string',
            'file_state.*.note' => 'nullable|string',
            'file_state.*.reason' => 'nullable|string',
            'file_state.*.state_code' => 'nullable|string',
            'file_state.*.state_name' => 'nullable|string',

            // Account State
            'account_state' => 'nullable|array',
            'account_state.note' => 'nullable|string',
            'account_state.reason' => 'nullable|string',
            'account_state.state_code' => 'nullable|string',
            'account_state.state_name' => 'nullable|string',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Step 3: Proceed to helper method to add the staff
        return VendorManageHelper::UpdateVendorWithAccount($request);
    }


    //
}
