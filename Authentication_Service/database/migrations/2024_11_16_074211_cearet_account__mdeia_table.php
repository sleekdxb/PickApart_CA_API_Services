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
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('account_media', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key, 64-bit integer
            $table->string('acc_id', 255); // hashed value, assuming string of length 255
            $table->string('file_name'); // file name (string)
            $table->string('file_path'); // file path (string)
            $table->integer('file_size'); // file size (integer, stored in bytes)
            $table->string('media_type'); // media type (e.g., 'image/jpeg', 'video/mp4')
            $table->timestamp('upload_date')->useCurrent(); // auto set to current timestamp when uploaded
            $table->timestamps(); // created_at and updated_at timestamps

            // Foreign key constraint: acc_id references the Account table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade'); // Assuming 'accounts' table and 'acc_id' is the primary key

            // Indexing the acc_id column if you want to query by account ID faster
            $table->index('acc_id');
        });
    }
};
