<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Helpers\PackageHelper;

use Illuminate\Http\Request;

class PackageController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }

    public function addPackage(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        // Validate incoming request to check for acc_id existence and uniqueness
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'currency' => 'required|string',
            'payment_type' => 'required|string',
            'price' => 'required|string',
            'features' => 'required|json',
            'description' => 'required|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // If validation passes, call the helper method and return its response
        return PackageHelper::addPackage($request);
    }


    public function getPackage(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        return PackageHelper::getPackage($request);
    }
}
