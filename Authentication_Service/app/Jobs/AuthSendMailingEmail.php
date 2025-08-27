<?php

namespace App\Jobs;


use App\Mail\MailingData; // Assuming you're using a custom Mail class for mailingData
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MailingServiceConnections;
class AuthSendMailingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailingData;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $mailingData
     * @return void
     */
    public function __construct($mailingData)
    {
        $this->mailingData = $mailingData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Dispatch the email sending logic here
        $mailingRespond = MailingServiceConnections::authSendEmail($this->mailingData);

    }
}
