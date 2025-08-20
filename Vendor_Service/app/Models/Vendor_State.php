<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class Vendor_State extends Model
{
    // If the table name is not the default (vendor__states), define it explicitly
    protected $table = 'vendor_states'; // Change this if needed

    // If the primary key is not "id", specify it
    protected $primaryKey = 'id';

    // If the primary key is not an integer or not auto-incrementing
    public $incrementing = true;
    protected $keyType = 'int';

    // If you want timestamps to be auto-managed by Laravel
    public $timestamps = true;

    // Define the fillable columns
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
        'created_at',
        'updated_at',
    ];


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
        return $this->belongsTo(Vendor::class, 'vend_id'); // vend_id is the foreign key
    }
}
