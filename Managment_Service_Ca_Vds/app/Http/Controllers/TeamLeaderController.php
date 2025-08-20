<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Helpers\TeamLeaderHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;
use App\Models\Account; // Make sure the Account model is correctly imported
use Illuminate\Http\JsonResponse;

class TeamLeaderController extends Controller
{

    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;


    }
    public function setVendorState(Request $request)
    {
        // Validate headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Step 1: Basic validation (without checking for uniqueness yet)
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'doer_acc_id' => 'required|string',
            'vend_id' => 'required|string',
            'reason' => 'nullable|string',
            'note' => 'nullable|string',
            'state_name' => 'required|string',
            'state_code' => 'required|string',
            'time_period' => 'nullable|string',

        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }



        // Step 3: Proceed to helper method to add the staff
        return TeamLeaderHelper::setVendorState($request);
    }


    public function setDocState(Request $request)
    {
        // Validate headers
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Step 1: Basic validation (without checking for uniqueness yet)
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string',
            'doer_acc_id' => 'required|string',
            'vend_id' => 'required|string',
            'reason' => 'nullable|string',
            'note' => 'nullable|string',
            'state_name' => 'required|string',
            'state_code' => 'required|string',
            'time_period' => 'nullable|string',

        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }



        // Step 3: Proceed to helper method to add the staff
        return TeamLeaderHelper::setVendorAccountDocState($request);
    }
    public function getAccountsDataTest(Request $request): JsonResponse
    {
        $accounts = Account::query()->with([
            'account_states',
            'vendor',
            'vendor.mediaFiles.state',
            'memberships',


        ])->get();
        return response()->json([
            'success' => true,
            'data' => $accounts
        ], 200);
    }
}
