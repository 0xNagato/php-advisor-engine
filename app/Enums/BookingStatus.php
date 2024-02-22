<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case GUEST_ON_PAGE = 'guest_on_page';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
