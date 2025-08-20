<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\VendorDashboardLiveUpdate;
use App\Models\ChannelModel;

class GetVendorDashBoardData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get vendor dashboard data for each channel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch channels where 'channel_name' contains 'partListing', ordered by oldest first
        $channels = ChannelModel::where('channel_name', 'like', '%partListing%')
            ->orderBy('created_at', 'asc')
            ->get();

        // Loop through each filtered and sorted channel
        foreach ($channels as $channel) {
            // Extract the channel frequency from each channel
            $channel_frequency = $channel->channel_frequency;

            // Dispatch the event for each channel, passing the channel_frequency
            VendorDashboardLiveUpdate::dispatch($channel_frequency);

            // Optionally, log the channel name or frequency
           
        }
    }
}
