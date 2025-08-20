<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\RequestHelper;


class RequestController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function createRequest(Request $request)
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }

        $validator = Validator::make($request->all(), [
            'part_id' => 'required|string',
            'vend_id' => 'required|string',
            'sender_acc_id' => 'required|string',
            'message' => 'required|string',
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
        return RequestHelper::createRequest($request); // Call the method on the instance
    }

}
