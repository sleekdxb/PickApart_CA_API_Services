<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('part_media', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT can support up to 300,000,000)
            $table->id(); // This creates an auto-incrementing primary key column (BIGINT)

            // `part_id`: Hashed value linking to the Parts table
            $table->string('part_id', 255); // Assuming `part_id` is a hashed value

            // `description`: Description of the media file
            $table->text('description')->nullable(); // Nullable in case description is not provided

            // `file_name`: Name of the media file
            $table->string('file_name');

            // `file_path`: Path to the media file on the storage system
            $table->string('file_path');

            // `file_size`: Size of the media file in bytes
            $table->bigInteger('file_size'); // Using BIGINT to store large file sizes

            // `media_type`: Type of media (e.g., 'image/jpeg', 'video/mp4')
            $table->string('media_type');

            // `upload_date`: Date when the media file was uploaded
            $table->date('upload_date');

            // Timestamps: created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key linking `part_id` to the `parts` table
            $table->foreign('part_id')->references('part_id')->on('parts')->onDelete('cascade');

            // Optional: Index the `part_id` column for faster queries
            $table->index('part_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('part_media');
    }
};
