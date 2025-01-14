<?php

namespace App\Notifications\Concierge;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\View;

class ConciergeRegisteredEmail extends Notification
{
    use Queueable;

    public function __construct(
        private readonly bool $sendAgreementCopy = false,
        private readonly array $userData = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->from('welcome@primavip.co', 'PRIMA')
            ->subject('Welcome to PRIMA!')
            ->markdown('mail.concierge-registration-mail');

        if ($this->sendAgreementCopy) {
            $agreementHtml = View::make('terms.concierge-terms', [
                'user' => $notifiable,
                'date' => now()->format('F j, Y'),
            ])->render();

            $pdf = PDF::loadHTML($agreementHtml);

            $mailMessage->attachData(
                $pdf->output(),
                'prima-concierge-agreement.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $mailMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
