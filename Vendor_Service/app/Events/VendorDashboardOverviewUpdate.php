<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\Part;
use App\Models\ChannelModel;  // Assuming ChannelModel is the model for the channels
use Illuminate\Support\Facades\Log;

class VendorDashboardOverviewUpdate implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vendors;  // Array of all vendors
    public $channels; // Array of all channels

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Fetch all channels from the database
        $this->channels = ChannelModel::all();  // Assuming ChannelModel is the table holding channel data

        // Prepare vendor data grouped by vendor ID from the related channels
        $this->vendors = [];

        foreach ($this->channels as $channel) {
            // Fetch vendors using query() method on the Part model
            $this->vendors[$channel->vend_id] = Part::query() // Using query() instead of Eloquent's where()
                ->where('vend_id', $channel->vendor_id) // Fetch parts by vend_id
                ->with([
                    'inventory',
                    'vendor',
                    'image',
                    'partCategory',
                    'partName',
                    'notification'
                ])->get();
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Dynamically generate a channel name using channel_name and channel_frequency
        $channels = [];
        foreach ($this->channels as $channel) {
            // Construct the channel name using channel_name + channel_frequency (without 'vendor.partListing.')
            $channels[] = new Channel($channel->channel_name . '.' . $channel->channel_frequency);
        }

        return $channels;
    }

    /**
     * Get the data that should be broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Log the event broadcasting
        Log::info('Broadcasting vendor data for all vendors');

        // Return the vendor data based on each channel's vend_id
        return [
            'part_listing' => $this->vendors,  // Send all vendors grouped by channel
        ];
    }

    /**
     * Define the event name to be used on the frontend.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'VendorDashboardLiveUpdate'; // Event name
    }
}
