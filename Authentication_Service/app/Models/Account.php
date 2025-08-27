<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;   // ✅ JWT interface

class Account extends Authenticatable implements JWTSubject
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'accounts';

    // ✅ Your PK is a string (ULID/UUID): tell Eloquent
    protected $primaryKey = 'acc_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'acc_id',
        'action_state_id',
        'email',
        'password',
        'phone',
        'account_type',
        'fcm_token',
        'email_hash',
        'access_array',
        'profile_url',
        'system_state_id',
        'firstName',
        'lastName',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected $casts = [
        'access_array' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- JWTSubject ---
    public function getJWTIdentifier()
    {
        // MUST return a non-null string (be explicit)
        return (string) $this->getKey(); // -> acc_id
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
