<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;


use Illuminate\Http\Request;
use App\Helpers\VendorHelper;

class VendorController extends Controller
{
   
 public function getVendorMembership(Request $request)
    {
        // Validate incoming request to check for acc_id existence and uniqueness
        $validator = Validator::make($request->all(), [
            'acc_id' => 'required|string|exists:accounts,acc_id', // Validate acc_id exists in Account model
        ]);

        // If validation fails, return a response with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        // If validation passes, call the helper method and return its response
        return VendorHelper::getVendorMembership($request);
    }

}
