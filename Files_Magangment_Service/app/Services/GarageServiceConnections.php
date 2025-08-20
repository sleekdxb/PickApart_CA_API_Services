<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class GarageServiceConnections
{
    public static function setGarageProfile($acc_id, $id, $account, $profile, $file_expiry_data, $protocolTime)
    {
        try {
            $url = config('microservices.urls.setFileStateGarage');

            $payload = [
                'acc_id' => $acc_id,
                'id' => $id,
                'account' => $account,
                'profile' => $profile,
                'file_expiry_data' => $file_expiry_data,
                'upload_date' => $protocolTime
            ];

            Log::debug('Sending request to Garage API', ['url' => $url, 'payload' => $payload]);

            $promise = Http::async()->post($url, $payload);

            $response = $promise->wait();

            Log::debug('Garage API response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json()
            ]);

            return $response->json();

        } catch (RequestException $e) {
            Log::error('RequestException during setGarageProfile', ['message' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => 'Request exception: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('General error during setGarageProfile', ['message' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => 'Error during set file state process: ' . $e->getMessage(),
                'code' => 500,
                'data' => null
            ];
        }
    }
}
