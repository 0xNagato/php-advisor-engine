<?php

namespace App\Data;

use Illuminate\Notifications\AnonymousNotifiable;
use NotificationChannels\Twilio\TwilioChannel;
use Spatie\LaravelData\Data;

class RestaurantContactData extends Data
{
    public function __construct(
        public string $contact_name,
        public string $contact_phone,
        public bool   $use_for_reservations,
    )
    {
    }

    public function toNotifiable(): AnonymousNotifiable
    {
        return (new AnonymousNotifiable)->route(TwilioChannel::class, $this->contact_phone);
    }
}
