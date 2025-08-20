<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    // Optional: list of decryptable fields
    protected array $decryptable = [
        'note',
        'reason',
        'state_code',
        'state_name',
    ];

    // Decryption accessors
    public function getNoteAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getReasonAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getStateCodeAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getStateNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return original if not decryptable
        }
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vend_id'); // Corrected from 'state_id'
    }
}
