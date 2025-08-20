<?php
namespace App\Http\Controllers;
use App\Helpers\InventoryHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Inventory;
use Validator;
class InventroyController extends Controller
{
    protected $headerValidationController;
    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }
    public function setInventory(Request $request): JsonResponse
    {


        // Validate that the 'inventory' array and 'vend_id' are provided in the request
        $validated = Validator::make($request->all(), [
            'inventory' => 'required|json', // Ensure 'inventory' is an array
            'vend_id' => 'required|string',  // Ensure 'vend_id' is a string
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid input, inventory and vend_id are required.',
                'errors' => $validated->errors()
            ], 400);
        }
        try {
            // Call the helper function to add the part if validation passes
            return InventoryHelper::setInventory($request);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while setting the Inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInventory(Request $request): JsonResponse
    {
        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        // Validate the request to ensure 'vend_id' is provided and is a string
        $validated = Validator::make($request->all(), [
            'vend_id' => 'required|string', // 'vend_id' must be a required string
        ]);

        // Check if validation failed
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid input, vend_id must be a string.',
                'errors' => $validated->errors()
            ], 400);
        }

        // Retrieve the 'vend_id' from the request
        $vendId = $request->input('vend_id');

        // Ensure 'vend_id' is not empty
        if (empty($vendId)) {
            return response()->json([
                'status' => false,
                'message' => 'The vend_id is empty.',
            ], 400);
        }

        // Retrieve the inventory item that matches the provided vend_id
        $inventory = Inventory::where('vend_id', $vendId)->get();

        // Check if any inventory was found
        if ($inventory->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No inventories found for the provided vend_id.',
            ], 404);
        }

        // Return a JSON response with the inventory data
        return response()->json([
            'status' => true,
            'data' => $inventory
        ], 200);
    }

}
