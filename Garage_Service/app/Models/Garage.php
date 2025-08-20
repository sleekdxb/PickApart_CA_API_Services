<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Garage extends Model
{
    use HasFactory;

    // Specify the table name
    protected $table = 'garages';  // Update this with your actual table name if different.

    // Define primary key
    protected $primaryKey = 'id';

    // The primary key is not an auto-incrementing integer.
    public $incrementing = false;

    // The table uses timestamps
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // Define the fillable attributes (columns that can be mass-assigned)
    protected $fillable = [
        'acc_id',
        'gra_id',
        'garage_email',
        'business_phone',
        'garage_name',
        'garage_location',
        'country',
        'location',
        'long',
        'lat',
        'iAgreeToTerms',
        'state_id',
    ];

    // You can cast specific columns to their appropriate types
    protected $casts = [
        'iAgreeToTerms' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function files()
    {
        return $this->hasMany(AccountsMedia::class, 'gra_id', 'gra_id'); // vend_id is the foreign key
    }

    public function account()
    {
        return $this->hasMany(Account::class, 'acc_id', 'acc_id'); // vend_id is the foreign key
    }

}
