<?php

namespace App\Helpers;

use App\Events\MessageSent;
use App\Models\User;
use App\Models\Message;
use GetStream\StreamLaravel\StreamClient;
use Illuminate\Support\Facades\Log;

class VendorDashboardStreamHelper
{
    protected $client;

    public function __construct()
    {
        // Initialize the Stream client
        $this->client = new StreamClient(env('STREAM_KEY'), env('STREAM_SECRET'));
    }

    /**
     * Fetch live data and send a broadcast activity to users (via Laravel Broadcasting).
     *
     * @param int $userId
     * @param string $message
     * @return array
     */
    public function sendBroadcastActivityAndReturnData($userId, $message)
    {
        try {
            // Fetch user from the database (e.g., user info)
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Create a new message and save it
            $messageModel = Message::create([
                'user_id' => $userId,
                'content' => $message
            ]);

            // Prepare the activity data
            $broadcastData = [
                'message' => $messageModel->content,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_profile_picture' => $user->profile_picture,
                'time' => now()->toDateTimeString(),
            ];

            // Dispatch the broadcast event to notify the user
            broadcast(new MessageSent($broadcastData, $user));

            Log::info('Broadcasted message to user: ' . $userId);

            // Fetch live data from Stream (e.g., a feed related to this user)
            $feed = $this->client->feed('user', $userId);
            $liveData = $feed->get();  // Fetch the feed data in real-time from the Stream API

            // Return both the live data and the broadcasted message data
            return [
                'message' => $broadcastData['message'],
                'user_name' => $broadcastData['user_name'],
                'user_profile_picture' => $broadcastData['user_profile_picture'],
                'live_data' => $liveData, // This is the live data fetched from Stream API
            ];
        } catch (\Exception $e) {
            Log::error('Error broadcasting activity: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to broadcast and fetch data'], 500);
        }
    }
}
