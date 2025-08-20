<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use App\Models\Image; // Assuming you have an Image model to save the image data
use App\Models\Part;
use Illuminate\Support\Facades\DB;


define('PER_PAGE', 10);
class PartHelper
{
   public static function filterParts(Request $request)
{
    try {
        // 1️⃣ Determine the number of records per page
        //    - If 'per_page' is provided in request, use it (must be > 0)
        //    - Otherwise, use default PER_PAGE constant
        $perPage = ($request->filled('per_page') && (int)$request->input('per_page') > 0)
            ? (int)$request->input('per_page')
            : PER_PAGE;

        // 2️⃣ Determine the current page (optional, Laravel handles this automatically if omitted)
        $page = ($request->filled('page') && is_numeric($request->input('page')) && $request->input('page') > 0)
            ? (int)$request->input('page')
            : null;

        // 3️⃣ Start building query with eager loading
        $query = Part::query()->with([
            'inventory',
            'vendor',
            'image',
            'carModel',
            'partCategory',
            'manufacturer',
            'partName',
            'membership',
            'vendor_state'
        ]);

        // 🔍 Apply filters if parameters exist
        if ($request->filled('state_name')) {
            $query->whereHas('vendor_state', fn($q) => $q->where('state_name', $request->input('state_name')));
        }

        if ($request->filled('model_id')) {
            $modelId = $request->input('model_id');
            $query->whereHas('carModel', function ($q) use ($modelId) {
                $q->where('model_id', $modelId)->orWhere('name', $modelId);
            });
        }

        if ($request->filled('inve_class')) {
            $query->whereHas('inventory', fn($q) => $q->where('inve_class', $request->input('inve_class')));
        }

        if ($request->filled('make_id')) {
            $makeId = $request->input('make_id');
            $query->whereHas('manufacturer', function ($q) use ($makeId) {
                $q->where('make_id', $makeId)->orWhere('name', $makeId);
            });
        }

        if ($request->filled('cat_id')) {
            $query->whereHas('partCategory', fn($q) => $q->where('cat_id', $request->input('cat_id')));
        }

        if ($request->filled('part_name_id')) {
            $query->whereHas('partName', fn($q) => $q->where('part_name_id', $request->input('part_name_id')));
        }

        if ($request->filled('location')) {
            $query->whereHas('vendor', fn($q) => $q->where('location', $request->input('location')));
        }

        if ($request->filled('country')) {
            $query->whereHas('vendor', fn($q) => $q->where('country', $request->input('country')));
        }

        if ($request->filled('year')) {
            $query->where('year', 'like', '%' . $request->input('year') . '%');
        }

        // 📊 Sort options
        if ($request->filled('sort_by')) {
            switch (strtolower($request->input('sort_by'))) {
                case 'recommended first':
                    $query->whereHas('vendor_state', fn($q) => $q->where('state_name', 'Verified'))
                          ->whereHas('membership', fn($q) => $q->where('status', 'Active'))
                          ->orderBy('created_at', 'asc');
                    break;

                case 'original parts':
                    $query->whereHas('inventory', fn($q) =>
                        $q->whereRaw('LOWER(inve_class) LIKE ?', ['%original%'])
                    )->orderBy('created_at', 'asc');
                    break;

                case 'lowest priced':
                    $query->orderBy('retail_price', 'asc');
                    break;

                case 'highest priced':
                    $query->orderBy('retail_price', 'desc');
                    break;
            }
        }

        // 📍 Location-based filter using Haversine formula
        if ($request->filled(['lat', 'long', 'dis_value'])) {
            $lat1 = $request->input('lat');
            $long1 = $request->input('long');
            $disValue = $request->input('dis_value');

            $query->whereHas('vendor', function ($q) use ($lat1, $long1, $disValue) {
                $q->whereRaw("
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(vendors.lat)) 
                        * cos(radians(vendors.long) - radians(?)) 
                        + sin(radians(?)) * sin(radians(vendors.lat))
                    )) <= ?
                ", [$lat1, $long1, $lat1, $disValue]);
            });
        }

        // 📄 Paginate results
        $items = $page !== null
            ? $query->paginate($perPage, ['*'], 'page', $page)
            : $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => "Parts successfully fetched with {$perPage} records per page.",
            'data' => $items
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'An error occurred while fetching parts: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}



    /**
     * This method processes the part images and saves them to the database.
     */
public static function setFileStateById(Request $request)
{
    $request->validate([
        'part_id'     => 'required|string|max:255',
        'part_images' => 'required|array',
    ]);

    $part_id     = $request->input('part_id');
    $part_images = $request->input('part_images');

    try {
        DB::transaction(function () use ($part_id, $part_images) {

            // 1) Build a list of incoming filenames that have the target keywords
            $incomingWithKeywords = [];
            foreach ($part_images as $key => $value) {
                if (
                    stripos($key, 'Part_img') !== false ||
                    stripos($key, 'Q_&_A_') !== false
                ) {
                    // use the stem (filename without extension) for safer matching
                    $incomingWithKeywords[] = pathinfo((string)$key, PATHINFO_FILENAME);
                }
            }

            // 2) Delete old images that match (same part_id) + (keyworded names) + (basename match)
       Image::where('part_id', $part_id)
    ->where(function ($q) {
        $q->where('file_name', 'LIKE', '%Part_img%')
          ->orWhere('file_name', 'LIKE', '%Q_&_A_%');
    })
    ->delete();

            // 3) Insert fresh rows for all incoming images
            foreach ($part_images as $key => $value) {
                $fileName   = (string) $key;
                $fileUrl    = $value['url']        ?? null;
                $isUploaded = (bool)  ($value['uploaded']  ?? false);
                $media_type = $value['media_type'] ?? null;
                $file_size  = $value['file_size']  ?? null;

                // skip if critical fields are missing
                if (!$fileName || !$fileUrl) {
                    continue;
                }

                $file_id = hash('sha256', $fileName);

                $imageData = [
                    'part_id'    => $part_id,
                    'file_id'    => $file_id,
                    'file_name'  => $fileName,
                    'uploaded'   => $isUploaded,
                    'file_path'  => $fileUrl,
                    'media_type' => $media_type,
                    'file_size'  => $file_size,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                \App\Models\Image::create($imageData);
            }
        });

        return response()->json([
            'status'  => true,
            'message' => 'Images have been updated: old keyword-matching files deleted, new files saved.',
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Failed to update images.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


}