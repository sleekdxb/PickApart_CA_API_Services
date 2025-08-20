<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EncryptionHelper
{
    public static function handleEncryptRequest(Request $data)
    {
        // Validate the incoming data (expecting a dictionary where keys are strings and values are strings)
        $validator = Validator::make($data->all(), [
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'string', // Each element of the array must be a string (values only)
        ]);

        // If validation fails, return validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422); // 422 is for Unprocessable Entity (validation errors)
        }

        try {
            // Log the incoming data for debugging
            Log::debug('Encrypting data:', ['input_data' => $data->all()]);

            // Encrypt each value in the array while keeping the keys intact
            $encryptedData = [];
            foreach ($data->get('data') as $key => $value) {
                $encryptedData[$key] = Crypt::encryptString($value);
            }

            // Log the encrypted data (be cautious, as logging encrypted data can be sensitive)
            Log::debug('Encrypted data:', ['encrypted_data' => $encryptedData]);

            return response()->json([
                'status' => true,
                'message' => 'Data encrypted successfully.',
                'data' => $encryptedData,
            ], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            Log::error('Encryption failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'input_data' => $data->all()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Encryption failed: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public static function handleDecryptRequest(Request $encryptedData)
    {
        // Validate the incoming encrypted data (expecting a dictionary where keys are strings and values are encrypted strings)
        $validator = Validator::make($encryptedData->all(), [
            'data' => 'required|array', // Ensure 'data' is present and is an array
            'data.*' => 'string', // Each element of the array must be a string (encrypted values)
        ]);

        // If validation fails, return validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422); // 422 is for Unprocessable Entity (validation errors)
        }

        try {
            // Log the incoming encrypted data for debugging
            Log::debug('Decrypting data:', ['encrypted_data' => $encryptedData->all()]);

            // Decrypt each value in the array while keeping the keys intact
            $decryptedData = [];
            foreach ($encryptedData->get('data') as $key => $value) {
                $decryptedData[$key] = Crypt::decryptString($value);
            }

            // Log the decrypted data for debugging
            Log::debug('Decrypted data:', ['decrypted_data' => $decryptedData]);

            return response()->json([
                'status' => true,
                'message' => 'Data decrypted successfully.',
                'data' => $decryptedData,
            ], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            Log::error('Decryption failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'encrypted_data' => $encryptedData->all()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Decryption failed: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
