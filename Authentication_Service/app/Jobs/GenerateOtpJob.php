<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;  // Add this to use Cache facade
use App\Helpers\OtpHelper;
use Illuminate\Support\Facades\Log;
use App\Models\CacheItem;
class GenerateOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $accId;
    protected $deviceInfo;

    // Constructor to pass required data
    public function __construct($accId, $deviceInfo)
    {
       
        $this->accId = $accId;
        $this->deviceInfo = $deviceInfo;
    }

    // Handle the job (OTP generation logic)
    public function handle()
    {
        // Generate OTP
        $otpResponse = OtpHelper::generateOtp($this->accId, $this->deviceInfo);

        // Store the generated OTP in the cache with a unique key (using accId)
         CacheItem::setCacheValue($this->accId, $otpResponse, 20);  // Cache for 20 minutes

    }


    
}
