<?php

namespace App\Notifications\Concierge;

use App\Data\SmsData;
use App\Filament\Pages\Concierge\SpecialRequests;
use App\Models\SpecialRequest;
use App\Models\User;
use App\NotificationsChannels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VenueSpecialRequestAccepted extends Notification
{
    use Queueable;

    public string $venue;

    public string $link;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SpecialRequest $specialRequest,
    ) {
        $this->venue = $this->specialRequest->venue->name;
        $this->link = SpecialRequests::getUrl();
    }

    public function toSms(User $notifiable): SmsData
    {
        return new SmsData(
            phone: $notifiable->phone,
            templateKey: 'concierge_special_request_accepted',
            templateData: [
                'venue' => $this->venue,
                'link' => $this->link,
            ]
        );
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
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
