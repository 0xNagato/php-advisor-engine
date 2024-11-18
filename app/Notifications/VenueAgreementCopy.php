<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VenueOnboarding;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $venue_names = $this->onboarding->locations->pluck('name')->toArray();

        $pdf = PDF::loadView('pdfs.venue-agreement', [
            'company_name' => $this->onboarding->company_name,
            'venue_names' => $venue_names,
            'first_name' => $this->onboarding->first_name,
            'last_name' => $this->onboarding->last_name,
            'use_non_prime_incentive' => $this->onboarding->use_non_prime_incentive,
            'non_prime_per_diem' => $this->onboarding->non_prime_per_diem,
        ]);

        return (new MailMessage)
            ->subject('Your PRIMA Venue Agreement')
            ->greeting("Hello {$this->onboarding->first_name},")
            ->line('Thank you for completing the PRIMA venue onboarding process.')
            ->line('As requested, here is a copy of your agreement details:')
            ->line("Company Name: {$this->onboarding->company_name}")
            ->line("Signed By: {$this->onboarding->first_name} {$this->onboarding->last_name}")
            ->line('Date: '.$this->onboarding->created_at->format('F j, Y'))
            ->attachData(
                $pdf->output(),
                'prima-venue-agreement.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}
