<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // ✅ Makes the model authenticatable
use Illuminate\Notifications\Notifiable;               // ✅ For notifications
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;               // ✅ For JWT
use Laravel\Sanctum\HasApiTokens;                     // ✅ For Sanctum

class Account extends Authenticatable implements JWTSubject
{
    use HasFactory, HasApiTokens, Notifiable; // ✅ Traits in one line

    protected $table = 'accounts'; // Your custom table name

    protected $fillable = [
        'acc_id',
        'action_state_id',
        'email',
        'password',
        'phone',
        'account_type',
        'fcm_token',
        'access_array',
        'profile_url',
        'system_state_id',
        'firstName',
        'lastName',
        'created_at',
        'updated_at'
    ];

    /**
     * JWT: Get the identifier that will be stored in the JWT payload.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // The primary key (usually id)
    }
        protected $hidden = [
        'fcm_token'
    ];

    /**
     * JWT: Return a key value array, containing any custom claims to be added to JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; // You can return custom claims here
    }
}
