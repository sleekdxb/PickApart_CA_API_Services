<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\VendorDashboardLiveUpdate;
use App\Models\Vendor;
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
    protected $description = 'get vendor dashboard data';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        VendorDashboardLiveUpdate::dispatch();
    }
}
