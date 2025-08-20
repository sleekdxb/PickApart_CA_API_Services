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
         Schema::create('sessions', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key for the session record (ID)
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID
            $table->timestamp('end_time')->nullable(); // `end_time` to store when the session ends
            $table->string('ipAddress', 45); // `ipAddress` to store the client's IP address (IPv6 supported)
            $table->boolean('isActive')->default(true); // `isActive` to show if the session is still active
            $table->timestamp('lastAccessed')->nullable(); // `lastAccessed` for the last activity timestamp
            $table->text('sessionData')->nullable(); // `sessionData` to store session-related data
            $table->timestamp('start_time')->nullable(); // `start_time` for when the session started
            $table->text('acc_agent_info'); // `acc_agent_info` to store the client's agent info (User-Agent string)
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
