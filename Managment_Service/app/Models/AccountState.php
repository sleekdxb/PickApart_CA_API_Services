<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountState extends Model
{
    protected $table = 'account_states';

    protected $fillable = [
        'state_id',
        'acc_id',
        'doer_acc_id',
        'note',
        'reason',
        'state_code',
        'state_name',
        'time_period',
    ];
}
