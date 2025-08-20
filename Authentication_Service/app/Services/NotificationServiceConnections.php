<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NotificationServiceConnections
{


      public static function notifyAccountParties($dataToSend)
    {
        try {
            $url = config('microservices.urls.notifyAccountParties');
            // Send an asynchronous POST request to the mailing microservice
            $promise = Http::async()->post($url, [
                'acc_id' => $dataToSend['acc_id'],
                'notifiable_id' => $dataToSend['notifiable_id'],
                'type' => $dataToSend['type'],
                'data' => $dataToSend['data'],
            ]);

            // Wait for the promise to resolve and get the response
            $response = $promise->wait();

            // Return the JSON response directly from the microservice
            return $response->json();
        } catch (\Exception $e) {
            // If an error occurs during the process, return the error message as JSON
            return [
                'status' => false,
                'message' => 'Error during mailing process: ' . $e->getMessage(),
                'data' => null
            ];
        }



    }    
}
