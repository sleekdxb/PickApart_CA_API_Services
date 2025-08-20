<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Account extends Model implements JWTSubject
{
    use HasFactory;

    protected $table = 'accounts';

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
        'profile_url',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the identifier that will be stored in the JWT payload.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
