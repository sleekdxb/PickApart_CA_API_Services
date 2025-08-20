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
         Schema::create('accounts', function (Blueprint $table) {
            $table->id(); // Auto-incrementing unsigned big integer (ID)
            $table->string('acc_id', 255)->unique(); // `acc_id` as string and unique
            $table->string('action_state_id'); // `action_state_code` as string
            $table->string('email')->unique(); // `email` as string and unique
            $table->string('password'); // `password` as string
            $table->string('phone')->nullable(); // `phone` as string, nullable
            $table->integer('state'); // `state` as integer
            $table->string('system_state_id', 255); // `system_state_code` as string
            $table->string('firstName'); // `first name` as string 
            $table->string('lastName'); // `last name` as string  
            $table->timestamps(); // `created_at`  and `updated_at` timestamps
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
