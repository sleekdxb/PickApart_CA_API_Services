<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\PartsImagesHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Garage;
use App\Models\Vendor;  // Assuming the Vendor model exists
use Illuminate\Support\Facades\Log;  // Add the Log facade for logging
use App\Models\Part;
use App\Http\Controllers\HeaderValidationController;

class PartsImageController extends Controller
{
  //  protected $headerValidationController;

   // public function __construct(HeaderValidationController $headerValidationController)
  //  {
     //   $this->headerValidationController = $headerValidationController;
  //  }

    public function uploadPartsImage(Request $request): JsonResponse
    {
        // Call the header validation from the HeaderValidationController
      //  $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
      //  if ($headerValidationResponse) {
        //    return $headerValidationResponse;
      //  }
        try {
            $validator = Validator::make($request->all(), [
                'upload_protocol' => 'required|json', // Validate upload_protocol as an array
                'part_images' => 'required|file|mimes:zip', // Allow an array of files, including binary
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422); // HTTP status code 422 for validation errors
            }

            $uploadProtocol = json_decode($request->input('upload_protocol'), true);

            // Retrieve upload_protocol id and type
            $protocolVendorId = $uploadProtocol['vend_id'];
            $protocolInventoryId = $uploadProtocol['inve_id'];
            $protocolPartId = $uploadProtocol['part_id'];

            $vendor = Vendor::where('vend_id', $protocolVendorId)->get();
            $part = Part::where('part_id', $protocolPartId)->get();

            // Check if the type is either 'Garage' or 'Vendor'
            if (!$vendor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Protocol vend_id , Vendor not found',
                    'data' => []
                ], 400); // HTTP status code 400 for bad request
            }

            if (!$part) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Protocol part_id , Part not found',
                    'data' => []
                ], 400); // HTTP status code 400 for bad request
            }


            // If validation passes and record exists, proceed to upload image
            return PartsImagesHelper::UploadPartImage($request);

        } catch (\Exception $e) {
            // Handle unexpected errors and return a 500 status code with the error message
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error',
                'data' => ['error' => $e->getMessage()]
            ], 500); // HTTP status code 500 for server-side errors
        }
    }

}