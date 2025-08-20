<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\VendorDashboardOverviewUpdate;
class GetVendorOverview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-vendor-overview';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        VendorDashboardOverviewUpdate::dispatch();
    }
}
