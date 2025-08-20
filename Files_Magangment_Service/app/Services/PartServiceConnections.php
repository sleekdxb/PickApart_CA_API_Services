<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartServiceConnections
{
    public static function setPartFileState($part_id, $part_image)
    {
        try {
            $url = config('microservices.urls.setPartFileState');


            // Send an asynchronous POST request
            $promise = Http::async()->post($url, [
                'part_id' => $part_id,
                'part_images' => $part_image,
            ]);

            // Wait for the promise to resolve
            $response = $promise->wait();

            // Return the JSON response from the encryption microservice
            return $response->json();

        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during set file state  process: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }


}