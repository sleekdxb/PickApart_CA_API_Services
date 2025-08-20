<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications_vendor';  // Change this if your table name is different

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false; // Since the 'id' is a big integer, it's probably non-incrementing

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string'; // If you're using a non-incrementing big integer, set this as 'string'

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'acc_id',
        'type',
        'data',
        'read',
        'read_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Disable timestamps if you want to manage the created_at and updated_at fields manually.
     *
     * @var bool
     */
    public $timestamps = true;

    public function part()
    {
        return $this->belongsTo(Part::class, 'vend_id'); // vend_id is the foreign key
    }
}
