<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class VendorServiceConnections
{
    public static function setVendorProfile($acc_id, $main)
    {
        try {
            $url = config('microservices.urls.setProfile');

            // Send an asynchronous POST request
            $promise = Http::async()->post($url, [
                'acc_id' => $acc_id,
                'main' => $main
            ]);

            // Wait for the promise to resolve
            $response = $promise->wait();

            // Return the JSON response from the encryption microservice
            return $response->json();

        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during set profile  process: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }


}