<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicTalkToPrimaMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{role:string,name:string,company:?string,email:?string,phone:string,city:?string,preferred_contact_time:?string,message:?string}  $data
     */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Talk to PRIMA Form Submission (Public API)',
        );
    }

    public function content(): Content
    {
        $body = 'Role: '.$this->data['role']."\n\n".
            'Name: '.$this->data['name']."\n\n".
            'Company/Property: '.($this->data['company'] ?? 'Not provided')."\n\n".
            'Email: '.($this->data['email'] ?? 'Not provided')."\n\n".
            'Phone: '.$this->data['phone']."\n\n".
            'City: '.($this->data['city'] ?? 'Not provided')."\n\n".
            'Preferred Contact Time: '.($this->data['preferred_contact_time'] ?? 'Not provided')."\n\n".
            'Message: '.($this->data['message'] ?? 'Not provided');

        return new Content(
            text: 'mail.raw',
            with: [
                'content' => $body,
            ],
        );
    }
}
