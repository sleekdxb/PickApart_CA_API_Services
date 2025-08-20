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
        Schema::create('account_states', function (Blueprint $table) {
            $table->id(); // Auto-incrementing unsigned big integer (ID)
            $table->string('state_id', 255)->unique();
            $table->string('acc_id', 255); // `acc_id` as string, to store the hashed value
            $table->string('doer_acc_id', 255); // `doer_acc_id` as string, to store the hashed value
            $table->text('note')->nullable(); // `note` as long text, nullable
            $table->text('reason')->nullable(); // `reason` as long text, nullable
            $table->string('state_code'); // `state_code` as string
            $table->string('state_name'); // `state_name` as string
            $table->time('time_period')->nullable(); // `time_period` as time, nullable
            $table->timestamps(); // `created_at` and `updated_at` timestamps

            // Foreign key constraints
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
            $table->foreign('doer_acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
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
