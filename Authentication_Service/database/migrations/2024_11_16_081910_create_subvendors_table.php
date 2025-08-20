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
        Schema::create('subvendors', function (Blueprint $table) {
    $table->id(); // Auto-incrementing primary key (BIGINT)

    // vend_id: Hashed vendor ID, linking to the vendors table
    $table->string('vend_id', 255); // Vendor ID (hashed string)

    // acc_id: Hashed account ID, linking to the accounts table
    $table->string('acc_id', 255); // Account ID (hashed string)

    // access_type: Store the access type for the subvendor
    $table->string('access_type'); // Access type (string)

    // email: Store the subvendor's email address
    $table->string('email')->unique(); // Unique email field for subvendor

    // job_title: Store the subvendor's job title
    $table->string('job_title'); // Job title of the subvendor

    // password: Store the subvendor's password (hashed)
    $table->string('password'); // Store hashed password

    // phone: Store the subvendor's phone number
    $table->string('phone')->nullable(); // Nullable phone field

    // first_name: Store the subvendor's first name
    $table->string('first_name'); // First name of the subvendor

    // last_name: Store the subvendor's last name
    $table->string('last_name'); // Last name of the subvendor

    // Timestamps: created_at and updated_at
    $table->timestamps(); // Automatically creates `created_at` and `updated_at`

    // Foreign key constraints
    // Linking `vend_id` to the `vend_id` in the `vendors` table
    $table->foreign('vend_id')->references('vend_id')->on('vendors')->onDelete('cascade');

    // Linking `acc_id` to the `acc_id` in the `accounts` table
    $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');

    // Optional: Indexing the `vend_id` and `acc_id` columns for faster lookups
    $table->index('vend_id');
    $table->index('acc_id');
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subvendors');
    }
};
