<?php

namespace App\Enums;

enum SpecialRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case AWAITING_SPEND = 'awaiting_spend';
    case AWAITING_REPLY = 'awaiting_reply';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
