<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EncryptionServiceConnections
{
    // Function to handle encryption by calling the microservice
    public static function encryptData($dataToEncrypt)
    {
        try {
            $url = config('microservices.urls.encryption');

            // Send an asynchronous POST request
            $promise = Http::async()->post($url, [
                'data' => $dataToEncrypt
            ]);

            // Wait for the promise to resolve
            $response = $promise->wait();

            // Return the JSON response from the encryption microservice
            return $response->json();

        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during encryption process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public static function decryptData($dataToDecrypt)
    {
        try {
            // Send an asynchronous POST request to the decryption microservice
            $url = config('microservices.urls.decryption');
            $promise = Http::async()->post($url, [
                'data' => $dataToDecrypt
            ]);

            // Wait for the promise to resolve and get the response
            $response = $promise->wait();

            // Return the JSON response from the decryption microservice
            return $response->json();
        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during decryption process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

}
