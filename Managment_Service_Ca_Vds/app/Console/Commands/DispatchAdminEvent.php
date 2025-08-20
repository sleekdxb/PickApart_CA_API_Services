<?php

namespace App\Console\Commands;

use App\Events\AdminEvent;
use Illuminate\Console\Command;

class DispatchAdminEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:admin-event {acc_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch an admin event to broadcast account details';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $acc_id = $this->argument('acc_id');

        // Dispatch the AdminEvent with the provided acc_id or without any acc_id
        AdminEvent::dispatch($acc_id);


        //  $this->info('AdminEvent dispatched successfully!');
    }
}
