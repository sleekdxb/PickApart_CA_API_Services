<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    // Define the table name if it differs from the plural form of the model name
    protected $table = 'vendor_states';

    // Specify the columns that are mass assignable
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

    // Disable the default incrementing behavior for ID
    public $incrementing = true;



    // If you want to manipulate the date columns
    protected $dates = ['time_period'];

    // Optional: Add any relationships, if needed (for example, one-to-many, many-to-many, etc.)

    public function vendor_state()
    {
        return $this->belongsTo(Vendor::class, 'vend_id'); // vend_id is the foreign key
    }
}
