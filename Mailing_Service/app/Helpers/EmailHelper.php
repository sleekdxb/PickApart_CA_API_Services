<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyAccountEmail; // We'll create this Mail class shortly

class EmailHelper
{
    public static function sendEmail($data)
    {
        // Send email via the CustomEmail Mailable
        Mail::to($data['email'])->send(new VerifyAccountEmail($data));
    }
}
