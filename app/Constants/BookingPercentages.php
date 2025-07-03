<?php

namespace App\Constants;

class BookingPercentages
{
    /**
     * The percentage of the booking fee that goes to the platform for concierge bookings.
     */
    public const int PLATFORM_PERCENTAGE_CONCIERGE = 20;

    /**
     * The percentage of the booking fee that goes to the platform for venue bookings.
     */
    public const int PLATFORM_PERCENTAGE_VENUE = 10;

    /**
     * The percentage of the booking fee that goes to the concierge for non-prime bookings.
     */
    public const int NON_PRIME_CONCIERGE_PERCENTAGE = 80;

    /**
     * The processing fee percentage for non-prime bookings charged to the venue.
     */
    public const int NON_PRIME_PROCESSING_FEE_PERCENTAGE = 10;

    /**
     * The percentage of the booking fee that the venue pays for non-prime bookings.
     * This is negative because it represents an outgoing payment from the venue.
     */
    public const int NON_PRIME_VENUE_PERCENTAGE = -100 - self::NON_PRIME_PROCESSING_FEE_PERCENTAGE;

    /**
     * The percentage of the remainder that goes to the first-level referral for prime bookings.
     */
    public const int PRIME_REFERRAL_LEVEL_1_PERCENTAGE = 10;

    /**
     * The percentage of the remainder that goes to the second-level referral for prime bookings.
     */
    public const int PRIME_REFERRAL_LEVEL_2_PERCENTAGE = 5;

    /**
     * The maximum percentage a partner can earn from the remainder after paying out the venue and concierge.
     */
    public const int MAX_PARTNER_EARNINGS_PERCENTAGE = 20;

    /**
     * The default revenue percentage for new VIP Access (QR) concierges.
     * This provides a single place to update the default value for future concierges.
     */
    public const int VIP_ACCESS_DEFAULT_PERCENTAGE = 50;
}
