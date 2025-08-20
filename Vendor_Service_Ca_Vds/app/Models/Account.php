<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;  // Import JWTSubject contract

class Account extends Model implements JWTSubject
{
    use HasFactory;

    protected $table = 'accounts';  // Name of your custom session table

    protected $fillable = [
        'acc_id',
        'action_state_id',
        'email',
        'password',
        'phone',
        'account_type',
        'access_array',
        'system_state_id',
        'firstName',
        'lastName',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the identifier that will be stored in the JWT payload.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // The primary key (id) will be used in the JWT payload
    }
    protected $hidden = [
        'fcm_token'
    ];

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
