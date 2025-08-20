<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorState extends Model
{
    protected $table = 'vendor_states';

    protected $fillable = [
        'state_id',
        'acc_id',
        'doer_acc_id',
        'vend_id',
        'note',
        'reason',
        'state_code',
        'state_name',
        'time_period',
    ];
}
