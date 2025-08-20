<?php
namespace App\Http\Controllers;

use App\Helpers\PartHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartController extends Controller
{
    protected $headerValidationController;

    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }

    public function filterParts(Request $request)
    {

        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        $validatedData = Validator::make($request->all(), [
            'make_id' => 'nullable|string',  // part_id should exist if provided
            'model_id' => 'nullable|string',
            'cat_id' => 'nullable|string',
            'part_name_id' => 'nullable|string',
            'inve_class' => 'nullable|string',
            'year' => 'nullable|string',
            'per_page' => 'nullable|integer',
            'page' => 'nullable|integer',
            'state_name' => 'nullable|string',  // assuming valid statuses are active or inactive
            'sort_by' => 'nullable|string',  // sorting can be empty but must be an array if provided
            'location' => 'nullable|string',
            'country' => 'nullable|string',  // location should also be an array if provided
            'long' => 'nullable|string',  // Optional decimal longitude validation
            'lat' => 'nullable|string',  // Optional decimal latitude validation
            'dis_value' => 'nullable|string',
        ]);

        // If validation fails, return response with errors
        if ($validatedData->fails()) {
            return response()->json([
                'errors' => $validatedData->errors(),
            ], 422);
        }

        // Process if validation passes
        return PartHelper::filterParts($request);
    }

    public function setFileStateById(Request $request)
    {
        // Validation
        $validatedData = Validator::make($request->all(), [
            'part_id' => 'required|string', // part_id should be a non-empty string
            'part_images' => 'required|array',
        ]);

        // Check if validation fails
        if ($validatedData->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validatedData->errors()->first(),
            ], 400);
        }

        // Check if 'part_images' is in the correct format (should be an associative array)
        $part_images = $request->input('part_images');

        // Validate that part_images contains key-value pairs and is a non-empty array
        if (!is_array($part_images) || empty($part_images)) {
            return response()->json([
                'status' => false,
                'message' => 'part_images data must be a non-empty array with key-value pairs.',
            ], 400);
        }

        // Now, we pass the data to the helper for further processing
        return PartHelper::setFileStateById($request);
    }


}