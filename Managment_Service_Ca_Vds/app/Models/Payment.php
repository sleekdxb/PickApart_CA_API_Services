<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Payment extends Model
{
    use HasFactory;

    // Table name (optional if it matches the model name pluralized)
    protected $table = 'payments';

    // Primary key (optional if it's "id")
    protected $primaryKey = 'id';

    // Allow mass assignment for these fields
    protected $fillable = [
        'acc_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_id',
        'status',
        'description',
    ];

    // Enable timestamps (true by default)
    public $timestamps = true;

    // Casts (optional, helps with data types)
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function memberships()
    {
        return $this->belongsTo(Memberships::class, 'model_id'); // vend_id is the foreign key
    }
}
