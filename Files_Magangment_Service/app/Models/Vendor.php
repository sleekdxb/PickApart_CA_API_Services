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
        'business_name', 
        'location', 
        'community',
        'office_building',
        'official_email',
        'official_phone',
        'owner_id_number',
        'owner_id_full_name',
        'trade_license_number',
        'profile_doc_state_array_id',
        'state_position',
        'isOwner',
        'created_at',
        'updated_at',
    ];
}
