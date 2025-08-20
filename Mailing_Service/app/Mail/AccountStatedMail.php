<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class AccountStatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @param array $data The data passed to the email view
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Retrieve the sender email and name from the .env file
        $fromEmail = env('MAIL_FROM_ADDRESS', 'support@pick-a-part.ca'); // Default value if not set
        $fromName = env('MAIL_FROM_NAME', 'Pickapart Canada'); // Default value if not set

        return new Envelope(
            from: new Address($fromEmail, $fromName), // Set sender dynamically from .env
            subject: $this->data['subject'], // Subject from the data passed
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.AccountStatedMail', // Ensure this view exists in resources/views/emails/verify_account.blade.php
            with: [
                'upper_info' => $this->data['upper_info'],
                'but_info' => $this->data['but_info'],
                'data' => $this->data['data'],
                'name' => $this->data['name'],   // Send name to the view
                'subject' => $this->data['subject'],  // Send subject to the view
                'emailMessage' => $this->data['message'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return []; // No attachments by default, but you can add them if needed
    }
}
