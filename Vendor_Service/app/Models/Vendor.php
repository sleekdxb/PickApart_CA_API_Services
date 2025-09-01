<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    // The name of the table
    protected $table = 'vendors';

    // Set the primary key for the model (if different from the default 'id')
    protected $primaryKey = 'id';

    // The attributes that are mass assignable
    protected $fillable = [
        'vend_id',
        'acc_id',
        'address',
        'main',
        'business_name',
        'location',
        'country',
        'long',
        'lat',
        'official_email',
        'official_phone',
        'owner_id_number',
        'owner_id_full_name',
        'trade_license_number',
        'i_admit_not_owner',
        'state_position',
        'isOwner',
        'state_id',
        'created_at',
        'updated_at',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class, 'vend_id'); // vend_id is the foreign key
    }

    public function files()
    {
        return $this->hasMany(AccountsMedia::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }

    public function vendor_state()
    {
        return $this->hasMany(Vendor_State::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }

    public function account_state()
    {
        return $this->hasMany(AccountState::class, 'acc_id', 'acc_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'vend_id', 'vend_id');
    }


}
