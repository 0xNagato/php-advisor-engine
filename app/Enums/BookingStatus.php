<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case GUEST_ON_PAGE = 'guest_on_page';

    case ABANDONED = 'abandoned';
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case VENUE_CONFIRMED = 'venue_confirmed';
    case COMPLETED = 'completed';

    case REFUNDED = 'refunded';

    case NO_SHOW = 'no_show';

    case PARTIALLY_REFUNDED = 'partially_refunded';

    public const array REPORTING_STATUSES = [
        self::CONFIRMED,
        self::VENUE_CONFIRMED,
        self::COMPLETED,
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
        self::CANCELLED,
        self::NO_SHOW,
    ];

    public const array NON_REPORTING_STATUSES = [
        self::ABANDONED,
        self::GUEST_ON_PAGE,
        self::PENDING,
    ];

    public const array PAYOUT_STATUSES = [
        self::CONFIRMED,
        self::VENUE_CONFIRMED,
        self::COMPLETED,
        self::PARTIALLY_REFUNDED,
    ];

    public const array REFUNDED_STATUSES = [
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
    ];

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::GUEST_ON_PAGE => 'Guest on Page',
            self::CANCELLED => 'Cancelled',
            self::ABANDONED => 'Abandoned',
            self::CONFIRMED => 'Confirmed',
            self::VENUE_CONFIRMED => 'Venue Confirmed',
            self::COMPLETED => 'Completed',
            self::REFUNDED => 'Refunded',
            self::NO_SHOW => 'No Show',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
        };
    }
}
