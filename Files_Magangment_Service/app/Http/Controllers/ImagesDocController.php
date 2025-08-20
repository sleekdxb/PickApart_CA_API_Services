<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ImagesDocHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Garage;
use App\Models\Account;
use App\Models\Vendor;  // Assuming the Vendor model exists
use Illuminate\Support\Facades\Log;  // Add the Log facade for logging
use App\Models\Part;
use App\Http\Controllers\HeaderValidationController;

class ImagesDocController extends Controller
{
    // protected $headerValidationController;

    // public function __construct(HeaderValidationController $headerValidationController)
    // {
    //     $this->headerValidationController = $headerValidationController;
    //}

    public function uploadImageOrDoc(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'upload_protocol' => 'required|json',
                'file_expiry_data' => 'nullable|json',
                'account_files' => 'nullable|file|mimes:zip',
                'profile_files' => 'nullable|file|mimes:zip',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $uploadProtocol = json_decode($request->input('upload_protocol'), true);
            $protocolId = $uploadProtocol['id'];
            $protocolType = $uploadProtocol['type'];

            // Allow Garage, Vendor, and STR
            if (!in_array($protocolType, ['Garage', 'Vendor', 'STR'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Protocol type, must be Garage, Vendor, or STR',
                    'data' => []
                ], 400);
            }

            // Validate record based on type
            if ($protocolType === 'Garage') {
                $garage = Garage::where('gra_id', $protocolId)->first();
                if (!$garage) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, Garage record not found',
                        'data' => ['id' => $protocolId]
                    ], 404);
                }
            } elseif ($protocolType === 'Vendor') {
                $vendor = Vendor::where('vend_id', $protocolId)->first();
                if (!$vendor) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, Vendor record not found',
                        'data' => ['id' => $protocolId]
                    ], 404);
                }
            } elseif ($protocolType === 'STR') {
                $str = Account::where('acc_id', $protocolId)->first(); // Adjust model and ID column as needed
                if (!$str) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Protocol id, STR record not found',
                        'data' => ['id' => $protocolId]
                    ], 404);
                }
            }

            // Proceed to upload
            return ImagesDocHelper::UploadImageOrDoc($request);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error',
                'data' => ['error' => $e->getMessage()]
            ], 500);
        }
    }


    //----------------------------upload Part -------------------- images-------------------------

}
