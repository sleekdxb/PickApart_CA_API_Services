<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VendorAmendmentHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
class VendorAmendmentController extends Controller
{

    public function setVendorAmendment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|string',

            'amendment_type' => 'required|array',
            'change_request' => 'required|array',
            'acc_id' => 'required|string',
            'old_data_copy' => 'required|array',
            'new_data_copy' => 'required|array',
            'status' => 'required|string',
            'notes' => 'required|string',
            'reviewed_by' => 'required|string',
            'reviewed_at' => 'required|string',
            'reference_id' => 'required|string',
            'reference_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            // Get the first validation error message dynamically
            $firstError = $validator->errors()->first();

            return response()->json([
                'status' => false,
                'message' => $firstError,
                'errors' => $validator->errors()
            ], 400);
        }

        return VendorAmendmentHelper::setVendorAmendment($request);
    }


}
