<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\VendorAmendment;
use Log;

class VendorAmendmentHelper
{
    public static function setVendorAmendment(Request $request): JsonResponse
    {
        try {
            // Extract old and new data copies
            $old_data_copy = self::extractKeysWithDefaults([
                "firstName",
                "lastName",
                "account_type",
                "main",
                "business_name",
                "location",
                "long",
                "lat",
                "address",
                "country",
                "official_email",
                "official_phone",
                "owner_id_number",
                "owner_id_full_name",
                "state_position",
                "isOwner",
                "i_admit_not_owner",
            ], $request->input('old_data_copy'));

            $new_data_copy = self::extractKeysWithDefaults([
                "firstName",
                "lastName",
                "account_type",
                "main",
                "business_name",
                "location",
                "long",
                "lat",
                "address",
                "country",
                "official_email",
                "official_phone",
                "owner_id_number",
                "owner_id_full_name",
                "state_position",
                "isOwner",
                "i_admit_not_owner",
            ], $request->input('new_data_copy'));


            $notes = self::extractKeysWithDefaultsFilestate([
                "code",
                "type",
                "record"
            ], $request->input('notes'));

            // Create amendment record
            $amendment = VendorAmendment::create([
                'staff_id' => $request->input('staff_id'),
                'acc_id' => $request->input('acc_id'),
                'amendment_type' => json_encode($request->input('amendment_type')),
                'change_request' => json_encode($request->input('change_request')),
                'original_data' => json_encode($old_data_copy),
                'updated_data' => json_encode($new_data_copy),
                'status' => $request->input('status'),
               'notes' => $request->input('notes'),
                'reviewed_by' => $request->input('reviewed_by'),
                'reviewed_at' => $request->input('reviewed_at'),
                'reference_id' => $request->input('reference_id'),
                'reference_type' => $request->input('reference_type'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vendor amendment saved successfully.',
                'data' => $amendment,
            ], 200);

        } catch (\Exception $e) {
            Log::error('VendorAmendmentHelper Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while setting amendment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private static function extractKeysWithDefaults(array $keys, $source): array
    {
        $source = is_array($source) ? $source : [];
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $source[$key] ?? null;
        }

        return $result;
    }

    private static function extractKeysWithDefaultsFilestate(array $keys, $source): array
    {
        // Normalize source to array
        $source = is_array($source) ? $source : [];

        // Check if it's an array of arrays (multi-record)
        $isMulti = isset($source[0]) && is_array($source[0]);

        if ($isMulti) {
            // Handle array of arrays
            $result = [];
            foreach ($source as $item) {
                $filtered = [];
                foreach ($keys as $key) {
                    $filtered[$key] = $item[$key] ?? null;
                }
                $result[] = $filtered;
            }
            return $result;
        } else {
            // Handle single record
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $source[$key] ?? null;
            }
            return $result;
        }
    }
}
