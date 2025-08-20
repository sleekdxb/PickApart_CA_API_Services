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
        Schema::create('sent_emails', function (Blueprint $table) {
    $table->id(); 
    $table->string('recipient_id', 255);
    $table->string('sender_id', 255)->nullable();  // Auto-incrementing ID for each record
    $table->string('recipient_name');  // Recipient's email address
    $table->string('subject');    // Subject of the email
    $table->text('body');         // Body/content of the email
    $table->enum('status', ['sent', 'failed'])->default('sent'); // Email status (sent/failed)
    $table->timestamp('sent_at')->nullable(); // Time when the email was sent
    $table->timestamps(); 
    
    // Adding foreign keys with cascade on delete
    $table->foreign('recipient_id')->references('acc_id')->on('accounts')->onDelete('cascade'); 
    $table->foreign('sender_id')->references('acc_id')->on('accounts')->onDelete('cascade'); 

    // Adding indexes
    $table->index('recipient_id'); // Index for recipient_id for faster lookups
    $table->index('sender_id'); // Index for sender_id for faster lookups
    $table->index('status'); // Index for status for faster filtering by status
    $table->index('sent_at'); // Index for sent_at to improve performance on time-based queries
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sent_emails');
    }
};
