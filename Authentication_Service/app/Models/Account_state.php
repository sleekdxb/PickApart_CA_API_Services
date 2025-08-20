<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account_state extends Model
{
    use HasFactory;

    protected $table = 'account_states';  // Name of your custom session table

    protected $fillable = [
        'acc_id',
        'state_id',
        'doer_acc_id',
        'note',
        'reason',
        'state_code', 
        'state_name', 
        'time_period',
        'created_at',
        'updated_at'
    ];
}
