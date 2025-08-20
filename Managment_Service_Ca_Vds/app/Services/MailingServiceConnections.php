<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailingServiceConnections
{
    public static function sendEmailVendor($dataToSend)
    {
        try {
            $url = config('microservices.urls.emailsVendor');

            if (!$url) {
                Log::error('Email Vendor URL not found in config.');
                return [
                    'status' => false,
                    'message' => 'Email vendor microservice URL is not configured.',
                    'data' => null
                ];
            }

            Log::info('Sending vendor email to URL: ' . $url, $dataToSend);

            $promise = Http::async()->post($url, [
                'sender_id' => $dataToSend['sender_id'],
                'recipient_id' => $dataToSend['recipient_id'],
                'email' => $dataToSend['email'],
                'name' => $dataToSend['name'],
                'upper_info' => $dataToSend['upper_info'],
                'but_info' => $dataToSend['but_info'],
                'account_type' => $dataToSend['account_type'],
                'message' => $dataToSend['message'],
                'subject' => $dataToSend['subject'],
                'data' => $dataToSend['data'],
            ]);

            $response = $promise->wait();

            Log::info('Vendor email response received.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error while sending vendor email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Error during mailing process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public static function sendEmailGarage($dataToSend)
    {
        try {
            $url = config('microservices.urls.emailsGarage');

            if (!$url) {
                Log::error('Email Garage URL not found in config.');
                return [
                    'status' => false,
                    'message' => 'Email garage microservice URL is not configured.',
                    'data' => null
                ];
            }

            Log::info('Sending garage email to URL: ' . $url, $dataToSend);

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

            $response = $promise->wait();

            Log::info('Garage email response received.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error while sending garage email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Error during mailing process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }


    public static function sendEmailAccount($dataToSend)
    {
        try {
            $url = config('microservices.urls.setAccountStatedMail');

            if (!$url) {
                Log::error('Email Vendor URL not found in config.');
                return [
                    'status' => false,
                    'message' => 'Email vendor microservice URL is not configured.',
                    'data' => null
                ];
            }

            Log::info('Sending vendor email to URL: ' . $url, $dataToSend);

            $promise = Http::async()->post($url, [
                'sender_id' => $dataToSend['sender_id'],
                'recipient_id' => $dataToSend['recipient_id'],
                'email' => $dataToSend['email'],
                'name' => $dataToSend['name'],
                'upper_info' => $dataToSend['upper_info'],
                'but_info' => $dataToSend['but_info'],
                'account_type' => $dataToSend['account_type'],
                'message' => $dataToSend['message'],
                'subject' => $dataToSend['subject'],
                'data' => $dataToSend['data'],
            ]);

            $response = $promise->wait();

            Log::info('Vendor email response received.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error while sending vendor email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => 'Error during mailing process: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

}
