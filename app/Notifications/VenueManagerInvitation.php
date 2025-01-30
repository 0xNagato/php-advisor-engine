<?php

namespace App\Notifications;

use App\Models\Referral;
use App\Models\VenueGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VenueManagerInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    private string $invitationUrl;

    public function __construct(
        protected Referral $referral,
        protected VenueGroup $venueGroup
    ) {
        $this->invitationUrl = URL::signedRoute('venue-manager.invitation', [
            'referral' => $this->referral->id,
        ]);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You\'ve been invited to manage venues on PRIMA')
            ->greeting('Hello '.$this->referral->first_name)
            ->line('You\'ve been invited by '.$this->referral->referrer->name.' to help manage venues for '.$this->venueGroup->name.' on PRIMA.')
            ->line('To get started, please click the button below to create your account:')
            ->action('Accept Invitation', $this->invitationUrl)
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
