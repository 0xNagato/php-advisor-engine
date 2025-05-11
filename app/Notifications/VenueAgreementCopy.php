<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Actions\GenerateVenueAgreement;
use App\Models\VenueOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueAgreementCopy extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly VenueOnboarding $onboarding
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pdfContent = GenerateVenueAgreement::run($this->onboarding);

        return (new MailMessage)
            ->subject('Your PRIMA Venue Agreement')
            ->greeting("Hello {$this->onboarding->company_name},")
            ->line('Thank you for completing the PRIMA venue onboarding process.')
            ->line('As requested, here is a copy of your agreement details:')
            ->line("Company Name: {$this->onboarding->company_name}")
            ->line("Signed By: {$this->onboarding->company_name}")
            ->line('Date: '.$this->onboarding->created_at->format('F j, Y'))
            ->attachData(
                $pdfContent,
                'prima-venue-agreement.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
