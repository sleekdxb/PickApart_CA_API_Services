<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryType extends Model
{
    use HasFactory;

    // The table associated with the model.
    protected $table = 'inventorytype';

    // The primary key associated with the table.
    protected $primaryKey = 'id';

    // Indicates if the model should be timestamped.
    public $timestamps = true;

    // The attributes that are mass assignable.
    protected $fillable = ['name', 'inve_type_id'];

    // The attributes that should be hidden for arrays.
    protected $hidden = [];

    // The attributes that should be cast.
    protected $casts = [];
}
