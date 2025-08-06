<?php

/** @noinspection PhpUnused */

/** @noinspection PhpUnusedParameterInspection */

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\NotificationsChannels\SmsNotificationChannel;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use ShortURL;

class CustomerBookingConfirmed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            SmsNotificationChannel::class,
        ];
    }

    /**
     * @throws ShortURLException
     */
    public function toSMS(Booking $notifiable): SmsData
    {
        $templateKey = match (true) {
            (bool) $notifiable->is_non_prime_big_group => 'customer_booking_confirmed_non_prime_big_group',
            (bool) $notifiable->is_prime && (bool) $notifiable->venue->is_omakase => 'customer_booking_confirmed_prime_omakase',
            (bool) $notifiable->is_prime => 'customer_booking_confirmed_prime',
            default => $this->getNonPrimeTemplateKey($notifiable),
        };

        $invoiceUrl = route('customer.invoice', $notifiable->uuid);
        $modifyUrl = URL::signedRoute('modify.booking', $notifiable->uuid);

        return new SmsData(
            phone: $notifiable->guest_phone,
            templateKey: $templateKey,
            templateData: [
                'venue_name' => $notifiable->venue->name,
                'booking_date' => $notifiable->booking_at->format('D M jS'),
                'booking_time' => $notifiable->booking_at->format('g:ia'),
                'guest_count' => $notifiable->guest_count,
                'invoice_url' => ShortURL::destinationUrl($invoiceUrl)->make()->default_short_url,
                'modify_url' => ShortURL::destinationUrl($modifyUrl)->make()->default_short_url,
                'concierge_name' => $notifiable->concierge->user->name,
            ]
        );
    }

    private function getNonPrimeTemplateKey(Booking $booking): string
    {
        $regionalSmsRegions = config('app.regional_sms.non_prime_regions', []);
        $venueRegion = $booking->venue->region;

        if (in_array($venueRegion, $regionalSmsRegions)) {
            return 'customer_booking_received_non_prime';
        }

        return 'customer_booking_confirmed_non_prime';
    }
}
