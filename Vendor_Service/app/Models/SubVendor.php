<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubVendor extends Model implements JWTSubject
{
    use HasFactory;
    // Define the table name (if it's different from the default plural form)
    protected $table = 'subvendors';

    // Specify the primary key field (optional, Laravel defaults to 'id')
    protected $primaryKey = 'id';

    // If the primary key is not an integer, you can specify its type
    protected $keyType = 'string';  // Since sub_ven_id appears to be a string

    // Disable auto-increment (optional, Laravel defaults to incrementing)
    public $incrementing = false;

    // Define the columns that are mass assignable
    protected $fillable = [
        'vend_id',
        'acc_id',
        'sub_ven_id',
        'access_array',
        'email',
        'job_title',
        'password',
        'phone',
        'first_name',
        'last_name',
        'is_blocked'
    ];


    // If you want to allow timestamp management automatically
    public $timestamps = true;


    /**
     * Get the identifier that will be stored in the JWT payload.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // The primary key (id) will be used in the JWT payload
    }

    /**
     * Get custom claims for the JWT payload.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; // Optional: Add any custom claims if needed
    }
}
