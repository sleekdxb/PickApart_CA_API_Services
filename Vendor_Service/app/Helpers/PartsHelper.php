<?php
namespace App\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Part;
use Carbon\Carbon;
class PartsHelper
{
    public static function addPart(Request $request): JsonResponse
    {
        try {
            // Check if a part with the same stock_id already exists
            $existingPart = Part::where('stock_id', $request->input('stock_id'))->first();

            if ($existingPart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A part with this stock_id already exists.',
                ], 409); // 409 Conflict
            }

            // Create a new Part
            $part = new Part();

            // Generate part_id based on vendor and inventory IDs
            $part->part_id = Hash::make($request->input('vend_id') . $request->input('inve_id') . now());

            // Set required fields
            $part->vend_id = $request->input('vend_id');

            if ($request->has('sub_ven_id') && $request->input('sub_ven_id') !== null) {
                $part->sub_ven_id = $request->input('sub_ven_id');
            }

            $part->inve_id = $request->input('inve_id');
            $part->make_id = $request->input('make_id');
            $part->model_id = $request->input('model_id');
            $part->cat_id = $request->input('cat_id');
            $part->sub_cat_id = $request->input('sub_cat_id');
            $part->stock_id = $request->input('stock_id');
            $part->quantity = $request->input('quantity');
            $part->year = $request->input('year');
            $part->description = $request->input('description');
            $part->sale_price = $request->input('sale_price');
            $part->retail_price = $request->input('retail_price');

            $part->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Part added successfully',
                'data' => $part
            ], 201);

        } catch (\Exception $e) {
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
            // Validate input (optional but recommended)
            $request->validate([
                'part_id' => ['required'],
            ]);

            $partId = $request->input('part_id');

            // Find the part by part_id
            $part = Part::where('part_id', $partId)->first();

            if (!$part) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Part not found',
                ], 404);
            }

            // Ensure created_at exists
            if (empty($part->created_at)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Deletion policy cannot be applied: missing created_at.',
                ], 422);
            }

            // Deletion allowed 30 days after creation
            $createdAt = $part->created_at instanceof Carbon ? $part->created_at : Carbon::parse($part->created_at);
            $allowedAt = $createdAt->copy()->addDays(30);
            $now = now();

            if ($now->lt($allowedAt)) {
                // Time remaining until deletion is allowed
                $remainingSeconds = $now->diffInSeconds($allowedAt);
                $remainingHuman = $allowedAt->diffForHumans($now, [
                    'parts' => 3,       // up to 3 components (e.g. "29 days 4 hours 12 minutes")
                    'join' => true,
                    'short' => false,
                    'syntax' => Carbon::DIFF_ABSOLUTE,
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'DELETION_NOT_ALLOWED_YET',
                    'message' => 'Deletion is not allowed until 30 days after creation.',
                    'part_id' => $partId,
                    'created_at' => $createdAt->toIso8601String(),
                    'deletion_allowed_at' => $allowedAt->toIso8601String(),
                    'time_until_allowed' => $remainingHuman,
                    'seconds_until_allowed' => $remainingSeconds,
                ], 403); // Forbidden (business rule)
            }

            // Delete the part
            $part->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Part deleted successfully',
                'part_id' => $partId,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the part',
                'error' => $e->getMessage(),
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

        // Set the updated_at field to the current time
        $part->updated_at = now();

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

