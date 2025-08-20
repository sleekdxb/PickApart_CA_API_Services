<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\VendorDashboardOverviewUpdate;
use App\Models\ChannelModel;
class GetVendorOverview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:overview';

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
        // Fetch all channels from the database
        $channels = ChannelModel::where('channel_name', 'like', '%overview%')
            ->orderBy('created_at', 'asc')
            ->get();

        // Loop through each channel
        foreach ($channels as $channel) {
            // Extract the channel frequency from each channel
            $channel_frequency = $channel->channel_frequency;

            // Dispatch the event for each channel, passing the channel_frequency
            VendorDashboardOverviewUpdate::dispatch($channel_frequency);

            // Optionally, log or output the dispatch action for monitoring
        }

    }
}
