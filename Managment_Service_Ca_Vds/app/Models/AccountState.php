<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    // Optional: list of decryptable fields
    protected array $decryptable = [
        'note',
        'reason',
        'state_code',
        'state_name',
    ];

    // Accessors to automatically decrypt fields
    public function getNoteAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }
    public function getStateNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getStateCodeAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getReasonAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // If not decryptable, return as-is
        }
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'model_id'); // Check if 'model_id' is correct
    }
}
