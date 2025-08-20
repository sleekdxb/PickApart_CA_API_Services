<?php
namespace App\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Part;
class PartsHelper
{
    public static function addPart(Request $request): JsonResponse
    {
        try {
            // Create a new Part using the validated request data
            $part = new Part();

            // Generate part_id based on vendor and inventory IDs
            $part->part_id = Hash::make($request->input('vend_id') . $request->input('inve_id') . now());

            // Set required fields from the request
            $part->vend_id = $request->input('vend_id');

            // Conditionally set sub_ven_id if it's set and not null
            if ($request->has('sub_ven_id') && $request->input('sub_ven_id') !== null) {
                $part->sub_ven_id = $request->input('sub_ven_id');
            }

            $part->inve_id = $request->input('inve_id');
            $part->make_id = $request->input('make_id');
            $part->model_id = $request->input('model_id');
            $part->cat_id = $request->input('cat_id');
            $part->sub_cat_id = $request->input('sub_cat_id');
            $part->sub_cat_id = $request->input('stock_id');
            $part->sub_cat_id = $request->input('quantity');
            $part->year = $request->input('year');
            $part->description = $request->input('description');
            $part->sale_price = $request->input('sale_price');
            $part->retail_price = $request->input('retail_price');

            // Save the part to the database
            $part->save();

            // Return a success response with the created part data
            return response()->json([
                'status' => 'success',
                'message' => 'Part added successfully',
                'data' => $part
            ], 201);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while adding the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //---------------------------------------------------------------------------------------
    public static function deletePart(Request $request): JsonResponse
    {
        try {
            // Retrieve the part_id from the request
            $partId = $request->input('part_id');

            // Find the part by part_id
            $part = Part::where('part_id', $partId)->first();

            // Check if the part exists
            if (!$part) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Part not found'
                ], 404);
            }

            // Delete the part
            $part->delete();

            // Return a success response indicating the part was deleted
            return response()->json([
                'status' => 'success',
                'message' => 'Part deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //-------------------------------------------------------------------------------------
    public static function updatePart(Request $request): JsonResponse
    {
        try {
            // Retrieve the part by part_id
            $part = Part::where('part_id', $request->input('part_id'))->first();

            if (!$part) {
                // If the part does not exist, return an error response
                return response()->json([
                    'status' => 'error',
                    'message' => 'Part not found'
                ], 404);
            }

            // Update fields if they are provided (not null)
            if ($request->has('vend_id')) {
                $part->vend_id = $request->input('vend_id');
            }
            if ($request->has('sub_ven_id')) {
                $part->sub_ven_id = $request->input('sub_ven_id');
            }
            if ($request->has('inve_id')) {
                $part->inve_id = $request->input('inve_id');
            }
            if ($request->has('make_id')) {
                $part->make_id = $request->input('make_id');
            }
            if ($request->has('model_id')) {
                $part->model_id = $request->input('model_id');
            }
            if ($request->has('cat_id')) {
                $part->cat_id = $request->input('cat_id');
            }
            if ($request->has('sub_cat_id')) {
                $part->sub_cat_id = $request->input('sub_cat_id');
            }
            if ($request->has('stock_id')) {
                $part->stock_id = $request->input('stock_id');
            }

            if ($request->has('quantity')) {
                $part->quantity = $request->input('quantity');
            }
            if ($request->has('year')) {
                $part->year = $request->input('year');
            }
            if ($request->has('description')) {
                $part->description = $request->input('description');
            }
            if ($request->has('sale_price')) {
                $part->sale_price = $request->input('sale_price');
            }
            if ($request->has('retail_price')) {
                $part->retail_price = $request->input('retail_price');
            }

            // Save the updated part to the database
            $part->save();

            // Return a success response with the updated part data
            return response()->json([
                'status' => 'success',
                'message' => 'Part updated successfully',
                'data' => $part
            ], 200);
        } catch (\Exception $e) {
            // Catch any exceptions and return a JSON response with the exception message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the part',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}

