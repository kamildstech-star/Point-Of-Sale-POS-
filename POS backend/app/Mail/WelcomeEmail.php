<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $subject;

    public function __construct($message, $subject)
    {
        $this->message = $message;
        $this->subject = $subject; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope()
    {
        return (new Envelope)
            ->subject($this->subject);
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->view('mail.welcomemail')
                    ->with([
                        'mailmessage' => $this->message,
                    ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments()
    {
        return [];
    }
}
