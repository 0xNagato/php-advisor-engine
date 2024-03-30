<?php

namespace App\Notifications;

use App\Models\ConciergeReferral;
use App\Models\User;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

class ConciergeReferredText extends Notification
{
    use Queueable;

    protected string $shortURL;

    protected User $referrer;

    /**
     * @throws ShortURLException
     */
    public function __construct(public ConciergeReferral $conciergeReferral)
    {
        $this->referrer = $this->conciergeReferral->concierge->user;

        $url = URL::temporarySignedRoute("concierge.invitation", now()->addDays(), [
            "conciergeReferral" => $this->conciergeReferral
        ]);

        $this->shortURL = ShortURL::destinationUrl($url)->make()->default_short_url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioChannel::class];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage|TwilioMessage
    {
        $name = $this->referrer->name;

        return (new TwilioSmsMessage())
            ->content("You've been invited to PRIMA by $name. Please click $this->shortURL to create your profile and start earning! Welcome aboard!");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
