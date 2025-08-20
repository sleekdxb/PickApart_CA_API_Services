<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use App\Models\Image; // Assuming you have an Image model to save the image data
use App\Models\Impression;

define('PER_PAGE', 10);
class ImpressionsHelper
{
    public static function setImpressions(Request $request)
    {

        $existingImpression = Impression::where('part_id', $request->input('part_id'))
            ->where('doer_id', $request->input('doer_id'))
            ->where('type', $request->input('type'))
            ->first();

        if ($existingImpression) {
            // If a matching impression exists, throw a validation error
            return response()->json([
                'status' => false,
                'message' => 'You can not add for the same part more than once',
                'data' => [],
            ], 401);
        }
        // Validate the incoming request data
        $imp_id = hash('sha256', $request->input('doer_id') . uniqid());

        // Create a new impression using the validated data
        Impression::create([
            'imp_id' => $imp_id,
            'doer_id' => $request->input('doer_id'),
            'part_id' => $request->input('part_id'),
            'vend_id' => $request->input('vend_id'),
            'acc_id' => $request->input('acc_id'),
            'type' => $request->input('type'),  // Using null if not provided
            'value' => $request->input('value'),

        ]);

        // Return a response (optional)
        return response()->json([
            'status' => true,
            'message' => 'Impression created successfully!',
            'data' => [],
        ], 200);
    }
}