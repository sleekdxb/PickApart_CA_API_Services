<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use APP\Models\Part;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class File extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Optionally specify the table name if it's different from the pluralized model name
    protected $table = 'part_media';

    // Fillable attributes for mass assignment
    protected $fillable = [
        'id',
        'part_id',
        'file_id',
        'file_name',
        'file_path',
        'file_size',
        'media_type',
        'upload_date',
        'created_at',
        'updated_at',
    ];

    // If you want to explicitly handle created_at & updated_at columns
    public $timestamps = true;



    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id'); // vend_id is the foreign key
    }
}
