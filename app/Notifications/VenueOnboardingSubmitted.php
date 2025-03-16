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
        $mailMessage = (new MailMessage)
            ->subject('New Venue Onboarding Submission')
            ->greeting('New Venue Onboarding')
            ->line("A new venue onboarding submission has been received from {$this->onboarding->company_name}.")
            ->line('Contact Information:')
            ->line("Name: {$this->onboarding->first_name} {$this->onboarding->last_name}")
            ->line("Email: {$this->onboarding->email}")
            ->line("Phone: {$this->onboarding->phone}");

        // Add partner information if available
        if ($this->onboarding->partnerUser) {
            $mailMessage->line("Partner: {$this->onboarding->partnerUser->first_name} {$this->onboarding->partnerUser->last_name}");
        } else {
            $mailMessage->line('Partner: Not specified');
        }

        // Add venue group information if this is from an existing venue manager
        if ($this->onboarding->venue_group_id) {
            $mailMessage->line("Submitted by existing venue manager for venue group: {$this->onboarding->venueGroup->name}");
        }

        return $mailMessage
            ->line("Number of venues: {$this->onboarding->venue_count}")
            ->action('Review Submission', url(config('app.platform_url')."/venue-onboardings/{$this->onboarding->id}"))
            ->line('Thank you for using PRIMA!');
    }
}
