<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case GUEST_ON_PAGE = 'guest_on_page';
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';

    case NO_SHOW = 'no_show';
}
