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
        Schema::create('staff', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT can support up to 300,000,000)
            $table->id(); // This creates an auto-incrementing primary key column (BIGINT)

            // `acc_id`: Hashed account ID (string) linked to the `accounts` table
            $table->string('acc_id', 255); // Assuming the `acc_id` is a hashed value, length 255

            // `first_name`: Staff's first name
            $table->string('first_name');

            // `last_name`: Staff's last name
            $table->string('last_name');

            // `job_title`: Staff's job title
            $table->string('job_title');

            // `phone`: Staff's phone number
            $table->string('phone')->nullable(); // Making phone number optional

            // `passport_number`: Passport number for staff member
            $table->string('passport_number')->nullable();

            // `passport_expire_date`: Passport expiration date
            $table->date('passport_expire_date')->nullable();

            // `salary`: Staff's salary
            $table->decimal('salary', 15, 2); // Salary with two decimal points (e.g., 9999999999.99)

            // `working_shift`: Employee's working shift (e.g., Morning, Evening, Night)
            $table->string('working_shift');

            // `department`: Department where the staff works (e.g., HR, IT, Sales)
            $table->string('department');

            // `job_description`: Description of the job role
            $table->text('job_description')->nullable(); // Job description can be null

            // Timestamps: created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key linking `acc_id` to the `accounts` table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');

            // Optional: Index the `acc_id` column for faster queries
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
        Schema::dropIfExists('staff');
    }
};
