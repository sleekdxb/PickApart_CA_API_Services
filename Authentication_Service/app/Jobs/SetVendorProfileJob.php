<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\VendorServiceConnections;

class SetVendorProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $vendorData;
    protected $main;  // Add a property for $main

    /**
     * Create a new job instance.
     *
     * @param  mixed   $vendorData
     * @param  mixed   $main
     * @return void
     */
    public function __construct($vendorData, $main)
    {
        $this->vendorData = $vendorData;  // Correct the property assignment
        $this->main = $main;              // Assign $main to the class property
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mailingRespond = VendorServiceConnections::setVendorProfile($this->vendorData, $this->main);
    }
}
