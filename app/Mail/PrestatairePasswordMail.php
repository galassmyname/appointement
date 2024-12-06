<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrestatairePasswordMail extends Mailable
{
    use Queueable, SerializesModels;


    public string $name;
    public string $password;
    public string $email;
     
    
    public function __construct(string $name, string $password,  string $email)
    {
        $this->name = $name;
        $this->password = $password;
        $this->email = $email;
    }

    public function build()
    {
        return $this->subject('Votre compte a été créé')
                    ->markdown('emails.user_password', [
                        'name' => $this->name,
                        'password' => $this->password,
                    ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send User Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
