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

    public $vendors;
    public $channels;
    public $channelFrequency;

    public function __construct($channelFrequency)
    {
        $this->channelFrequency = $channelFrequency;
        $this->channels = ChannelModel::where('channel_frequency', $channelFrequency)->get();
        $this->vendors = [];

        foreach ($this->channels as $channel) {
            $vendors = Part::query()
                ->where('vend_id', $channel->vendor_id)
                ->with([
                    'inventory',
                    'vendor',
                    'impressions',
                    'image',
                    'partCategory',
                    'partName',
                    'notification',
                    'account_notification'

                ])->paginate(15);

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
