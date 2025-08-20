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
          Schema::create('otps', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key for the OTP record
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID
            $table->string('otp', 6); // `otp` to store the OTP code (assuming it's a 6-digit OTP)
            $table->boolean('is_used')->default(false); // `is_used` to indicate if the OTP has been used
            $table->timestamp('expires_at'); // `expires_at` to store the expiration time of the OTP
            $table->text('device_info')->nullable(); // `device_info` to store the client device info (nullable)
            $table->timestamps(); // `created_at` and `updated_at` timestamps

            // Foreign key constraint: linking `acc_id` to the `accounts` table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
