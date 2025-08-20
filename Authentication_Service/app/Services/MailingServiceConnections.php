<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailingServiceConnections
{
    public static function sendEmail($dataToSend)
    {
        try {
            $url = config('microservices.urls.sendEmail');
            
            $promise = Http::async()->post($url, [
                'sender_id'     => $dataToSend['sender_id'],
                'recipient_id'  => $dataToSend['recipient_id'],
                'email'         => $dataToSend['email'],
                'name'          => $dataToSend['name'],
                'message'       => $dataToSend['message'],
                'subject'       => $dataToSend['subject'],
                'data'          => $dataToSend['data'],
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

    public static function sendEmailRgestration($dataToSend)
    {
        try {
            $url = config('microservices.urls.emailsRegistrationSuccessMail');

            $promise = Http::async()->post($url, [
                'sender_id'     => $dataToSend['sender_id'],
                'recipient_id'  => $dataToSend['recipient_id'],
                'email'         => $dataToSend['email'],
                'name'          => $dataToSend['name'],
                'message'       => $dataToSend['message'],
                'subject'       => $dataToSend['subject'],
                'data'          => $dataToSend['data'],
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
    
     public static function sendEmailResetPasswordParties($dataToSend)
    {
        //-------------------------------------------------------------------
         try {
            $url = config('microservices.urls.sendResetPasswordEmailParties');

            if (!$url) {

                return [
                    'status' => false,
                    'message' => 'Email garage microservice URL is not configured.',
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
                'account_type' => $dataToSend['account_type']
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
    
    //----------------------------------------------------------------------------------------
     public static function sendEmailAccount($dataToSend)
    {
        //-------------------------------------------------------------------
         try {
            $url = config('microservices.urls.sendResetPasswordEmailParties');

            if (!$url) {

                return [
                    'status' => false,
                    'message' => 'Email garage microservice URL is not configured.',
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
                'account_type' => $dataToSend['account_type']
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
