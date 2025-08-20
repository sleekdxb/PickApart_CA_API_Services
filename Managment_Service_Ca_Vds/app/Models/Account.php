<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject; // Contract to implement JWT support

use Illuminate\Support\Facades\Crypt;

class Account extends Model implements JWTSubject
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'acc_id',
        'action_state_id',
        'email',
        'phone',
        'account_type',
        'access_array',
        'system_state_id',
        'firstName',
        'lastName',
        'created_at',
        'updated_at'
    ];

    protected array $decryptable = [
        'email',
        'phone',
        'account_type',
        'access_array',
    ];

    protected $hidden = [
        'password',
        'id',
        'fcm_token'
    ];

    // Accessors for decryptable fields
    public function getEmailAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getPhoneAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getAccountTypeAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function getAccessArrayAttribute($value)
    {
        // Assuming this is stored as JSON
        return json_decode($this->decryptIfNeeded($value), true);
    }

    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If already decrypted or invalid format, return original
            return $value;
        }
    }

    // JWT methods...
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships...
    public function account_states()
    {
        return $this->hasMany(AccountState::class, 'acc_id', 'acc_id');
    }
    public function memberships()
    {
        return $this->hasMany(Memberships::class, 'acc_id', 'acc_id');
    }
    public function session()
    {
        return $this->hasMany(Session::class, 'acc_id', 'acc_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'acc_id', 'acc_id');
    }
    public function amendment()
    {
        return $this->hasMany(AmendmentVendor::class, 'acc_id', 'acc_id');
    }
    public function vendor()
    {
        return $this->hasMany(Vendor::class, 'acc_id', 'acc_id');
    }
    public function files()
    {
        return $this->hasMany(AccountMediaFile::class, 'vend_id', 'vend_id');
    }
    public function files_state()
    {
        return $this->hasMany(AccountMediaState::class, 'acc_id', 'acc_id');
    }
    public function profile_states()
    {
        return $this->hasMany(VendorState::class, 'acc_id', 'acc_id');
    }
}
