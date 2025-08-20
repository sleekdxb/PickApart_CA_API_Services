<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use App\Models\Inventory;
use Log;

class InventoryHelper
{
    public static function setInventory(array $inventoryArray, string $vendId): JsonResponse
    {
        if (!is_array($inventoryArray) || empty($inventoryArray)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory data should be a non-empty associative array of class types.',
            ], 400);
        }

        $processedInventory = array_map(function ($className, $classType) use ($vendId) {
            return [
                'vend_id' => $vendId,
                'inve_class' => $classType,
                'inve_id' => hash('sha256', uniqid('inve_' . now(), true)),
                'created_at' => now(),
            ];
        }, array_keys($inventoryArray), $inventoryArray);

        Inventory::insert($processedInventory);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory has been successfully created.',
            'data' => $processedInventory
        ], 201);
    }
}
