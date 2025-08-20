<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    // Table name (optional if it matches the model name pluralized)
    protected $table = 'packages';

    // Primary key (optional if it's "id")
    protected $primaryKey = 'id';

    // Allow mass assignment for these fields
    protected $fillable = [
        'pak_id',
        'name',
        'currency',
        'payment_type',
        'price',
        'features',
        'description',
        'created_at',
          'updated_at',
    ];

    // Enable timestamps (true by default)
    public $timestamps = true;

    // Casts (optional, helps with data types)
 
}
