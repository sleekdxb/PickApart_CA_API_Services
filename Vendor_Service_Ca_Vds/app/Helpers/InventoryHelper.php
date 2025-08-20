<?php

namespace App\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Inventory;
use Log;
class InventoryHelper
{


    public static function setInventory(array $inventoryArray, string $vendId): JsonResponse
    {
        // Check if inventory is properly decoded and is a non-empty array
        if (!is_array($inventoryArray) || empty($inventoryArray)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory data should be a non-empty associative array of class types.',
            ], 400);
        }

        // Create an array of inventory items based on the provided class keys and values
        $processedInventory = array_map(function ($className, $classType) use ($vendId) {
            // Map each class to an inventory item with a generated unique inve_id and vend_id
            return [
                'vend_id' => $vendId,  // Add the vend_id to the item
                'inve_class' => $classType,  // Set the class type (Original/Manufacturer, After Market, Used/Scrap)
                'inve_id' => hash('sha256', uniqid('inve_' . now(), true)), // Generate a unique inve_id
                'created_at' => now(),  // Set the current timestamp for created_at
            ];
        }, array_keys($inventoryArray), $inventoryArray);

        // Save the processed inventory items to the database using bulk insert
        Inventory::insert($processedInventory); // Assumes your Inventory model is set up for bulk insert

        // Return a JSON response indicating success
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory has been successfully created.',
            'data' => $processedInventory
        ], 201);
    }




}