<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Part;
use App\Models\ChannelModel;

class VendorDashboardLiveUpdate implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channels;
    public $channelFrequency;

    public function __construct($channelFrequency)
    {
        $this->channelFrequency = $channelFrequency;
        $this->channels = ChannelModel::where('channel_frequency', $channelFrequency)->get();

        foreach ($this->channels as $channel) {
            // Get related vendor data
            $vendors = Part::query()
                ->where('vend_id', $channel->vendor_id)
                ->with([
                    'inventory',
                    'vendor',
                    'impressions',
                    'image',
                    'partCategory',
                    'partName',
                    'make',
                    'model',
                    'notification',
                    'account_notification'
                ])->paginate(15);


            // Save JSON to DB column
            $channel->latest_data = json_encode($vendors); // Make sure 'latest_data' exists in the DB
            $channel->save();
        }
    }

    public function broadcastOn()
    {
        return $this->channels->map(function ($channel) {
            return new Channel($channel->channel_name . '.' . $channel->channel_frequency);
        })->toArray();
    }

    public function broadcastWith()
    {
        return [
            'channelFrequency' => $this->channelFrequency,
            'url' => route('vendor.dashboard.update', ['frequency' => $this->channelFrequency]),
        ];
    }
}
