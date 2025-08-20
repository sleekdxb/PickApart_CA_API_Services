<?php
namespace App\Helpers;

use App\Models\InventoryType;
use Exception;

class InventoryTypeHelper
{
    public static function getInventoryTypes()
    {
        try {
            // Retrieve all inventory types
            $inventoryTypes = InventoryType::all();

            // Check if there is any data
            if ($inventoryTypes->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No inventory types found.',
                    'data' => [],
                ], 404);
            }

            // Return success response with data
            return response()->json([
                'status' => true,
                'message' => 'Inventory types fetched successfully.',
                'data' => $inventoryTypes,
            ], 200);

        } catch (Exception $e) {
            // Return error response if an exception occurs
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
