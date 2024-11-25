<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VenueOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueOnboardingSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly VenueOnboarding $onboarding
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Venue Onboarding Submission')
            ->greeting('New Venue Onboarding')
            ->line("A new venue onboarding submission has been received from {$this->onboarding->company_name}.")
            ->line('Contact Information:')
            ->line("Name: {$this->onboarding->first_name} {$this->onboarding->last_name}")
            ->line("Email: {$this->onboarding->email}")
            ->line("Phone: {$this->onboarding->phone}")
            ->line('')
            ->line("Number of venues: {$this->onboarding->venue_count}")
            ->action('Review Submission', url(config('app.platform_url')."/venue-onboardings/{$this->onboarding->id}"))
            ->line('Thank you for using PRIMA!');
    }
}
