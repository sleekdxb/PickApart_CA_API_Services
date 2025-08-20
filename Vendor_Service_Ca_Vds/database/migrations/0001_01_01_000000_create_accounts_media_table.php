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
       Schema::create('accounts_media', function (Blueprint $table) {
    $table->id(); // auto-incrementing primary key, 64-bit integer

    $table->string('acc_id', 225)->unique(); // hashed value, assuming string of length 255
    $table->string('graies_id', 225)->unique()->nullable(); // nullable if it may not be set
    $table->string('sub_ven_id', 225)->unique()->nullable(); // nullable if it may not be set
    $table->string('vendors_id', 225)->unique()->nullable(); // nullable if it may not be set
    $table->string('file_name'); // file name (string)
    $table->string('file_path'); // file path (string)
    $table->integer('file_size'); // file size (integer, stored in bytes)
    $table->string('media_type'); // media type (e.g., 'image/jpeg', 'video/mp4')
    $table->timestamp('upload_date')->useCurrent(); // auto set to current timestamp when uploaded
    $table->timestamps(); // created_at and updated_at timestamps

    // Foreign key constraint: acc_id references the Account table
    $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
	
    // Foreign key constraints for admin_id, graies_id, vendors_id
    
    $table->foreign('graies_id')->references('gra_id')->on('graies')->onDelete('cascade');
    $table->foreign('vendors_id')->references('vend_id')->on('vendors')->onDelete('cascade');
    $table->foreign('sub_ven_id')->references('sub_ven_id')->on('subvendors')->onDelete('cascade');
});

    }
};
