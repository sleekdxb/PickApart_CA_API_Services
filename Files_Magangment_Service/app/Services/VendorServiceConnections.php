<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class VendorServiceConnections
{
    public static function setVendorProfile($acc_id, $id, $account, $profile, $file_expiry_data, $protocolTime)
    {
        try {
            $url = config('microservices.urls.setFileStateVendor');

            // Send an asynchronous POST request
            $promise = Http::async()->put($url, [
                'acc_id' => $acc_id,
                'id' => $id,
                'account' => $account,
                'profile' => $profile,
                'file_expiry_data' => $file_expiry_data,
                'upload_date' => $protocolTime
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