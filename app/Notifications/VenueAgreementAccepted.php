<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VenueOnboarding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VenueAgreementAccepted extends Notification implements ShouldQueue
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
            ->subject('Venue Agreement Accepted')
            ->greeting('Venue Agreement Accepted')
            ->line("A venue agreement has been accepted by {$this->onboarding->company_name}.")
            ->line('Contact Information:')
            ->line("Name: {$this->onboarding->first_name} {$this->onboarding->last_name}")
            ->line("Email: {$this->onboarding->email}")
            ->line("Phone: {$this->onboarding->phone}")
            ->line("Agreement accepted at: {$this->onboarding->agreement_accepted_at?->format('M j Y, g:ia')}")
            ->line('Venue Information:');

        // Add venue details
        foreach ($this->onboarding->locations as $index => $location) {
            $mailMessage->line(($index + 1).". {$location->name} ({$location->region})");
        }

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
            ->action('View Onboarding', url(config('app.platform_url')."/venue-onboardings/{$this->onboarding->id}"))
            ->line('The venue is now ready for processing.')
            ->line('Thank you for using PRIMA!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
