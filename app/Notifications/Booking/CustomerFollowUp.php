<?php

namespace App\Notifications\Booking;

use App\Data\SmsData;
use App\Models\Booking;
use App\Models\VipCode;
use App\NotificationsChannels\SmsNotificationChannel;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerFollowUp extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Booking $booking) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return [SmsNotificationChannel::class];
    }

    public function toSms(): SmsData
    {
        return new SmsData(
            phone: $this->booking->guest_phone,
            templateKey: 'customer_booking_follow_up',
            templateData: [
                'venue_name' => $this->booking->venue->name,
                'link' => $this->getCalendarLink(),
            ]
        );
    }

    private function getCalendarLink(): string
    {
        $vipCode = Cache::remember(
            'available_calendar_button_vip_code_1',
            60,
            fn () => VipCode::query()->where('concierge_id', 1)->active()->first(),
        );

        $link = config('app.primary_domain').'/';
        $link .= ltrim(route('v.booking', ['code' => $vipCode->code], false), '/');

        return $link;
    }
}
