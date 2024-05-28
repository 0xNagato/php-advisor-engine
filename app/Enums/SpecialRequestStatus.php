<?php

namespace App\Enums;

enum SpecialRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case AwaitingSpend = 'awaiting_spend';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
