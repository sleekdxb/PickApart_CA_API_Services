<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MailingServiceConnections
{
    public static function sendEmail($dataToSend)
    {
        try {
            $url = config('microservices.urls.emailsRegistrationSuccessMail');
            // Send an asynchronous POST request to the mailing microservice
            $promise = Http::async()->post($url, [
                'sender_id' => $dataToSend['sender_id'],
                'recipient_id' => $dataToSend['recipient_id'],
                'email' => $dataToSend['email'],
                'name' => $dataToSend['name'],
                'upper_info' => $dataToSend['upper_info'],
                'but_info' => $dataToSend['but_info'],
                'message' => $dataToSend['message'],
                'subject' => $dataToSend['subject'],
                'data' => $dataToSend['data']
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
    
    public static function sendEmailVendor($dataToSend)
    {
        try {
            $url = config('microservices.urls.emailsVendor');

            if (!$url) {

                return [
                    'status' => false,
                    'message' => 'Email vendor microservice URL is not configured.',
                    'data' => null
                ];
            }



            $promise = Http::async()->post($url, [
                'sender_id' => $dataToSend['sender_id'],
                'recipient_id' => $dataToSend['recipient_id'],
                'email' => $dataToSend['email'],
                'name' => $dataToSend['name'],
                'upper_info' => $dataToSend['upper_info'],
                'but_info' => $dataToSend['but_info'],
                'message' => $dataToSend['message'],
                'subject' => $dataToSend['subject'],
                'data' => $dataToSend['data'],
                'account_type' => $dataToSend['account_type'],
            ]);

            $response = $promise->wait();



            return $response->json();

        } catch (\Exception $e) {


            return [
                'status' => false,
                'message' => 'Error during mailing process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
