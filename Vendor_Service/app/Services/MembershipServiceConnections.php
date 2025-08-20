<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class MembershipServiceConnections
{
    public static function sendEmail($dataToSend)
    {
        try {

            $response = Http::post('http://127.0.0.1:8004/api/get-membership-for-vendor', [
                'acc_id' => $dataToSend['acc_id'],
            ]);

            // Return the JSON response directly from the microservice

            return $response->json();
        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during getting membership data process:' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }


}