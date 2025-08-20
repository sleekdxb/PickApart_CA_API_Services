<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EncryptionServiceConnections
{
    // Function to handle encryption by calling the microservice
    public static function encryptData($dataToEncrypt)
    {
        try {
            // Send a POST request to the encryption microservice
            $response = Http::post('https://api-encryption-service.pickapart.ae/api/encrypt-data', [
                'data' => $dataToEncrypt
            ]);

            // Return the JSON response directly from the microservice
            return $response->json();
        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during encryption process: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }


     public static function decryptData($dataToEncrypt)
    {
        try {
            // Send a POST request to the encryption microservice
            $response = Http::post('https://api-encryption-service.pickapart.ae/api/decrypt-data', [
                'data' => $dataToEncrypt
            ]);

            // Return the JSON response directly from the microservice
            return $response->json();
        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during decryption process: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }
}
