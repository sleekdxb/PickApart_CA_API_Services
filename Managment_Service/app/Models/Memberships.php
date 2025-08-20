<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memberships extends Model
{
    protected $table = 'memberships';

    protected $fillable = [
        'memb_id',
        'transaction_id',
        'acc_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'allowance_level',
    ];
}


