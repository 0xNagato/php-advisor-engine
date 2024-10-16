<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EarningType: string implements HasLabel
{
    case VENUE = 'venue';
    case PARTNER_VENUE = 'partner_venue';
    case CONCIERGE = 'concierge';
    case PARTNER_CONCIERGE = 'partner_concierge';
    case CONCIERGE_REFERRAL_1 = 'concierge_referral_1';
    case CONCIERGE_REFERRAL_2 = 'concierge_referral_2';
    case VENUE_PAID = 'venue_paid';
    case CONCIERGE_BOUNTY = 'concierge_bounty';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VENUE => 'Venue',
            self::PARTNER_VENUE => 'Partner Venue',
            self::PARTNER_CONCIERGE => 'Partner Concierge',
            self::CONCIERGE => 'Concierge',
            self::CONCIERGE_REFERRAL_1 => 'Concierge Referral 1',
            self::CONCIERGE_REFERRAL_2 => 'Concierge Referral 2',
            self::VENUE_PAID => 'Venue Paid',
            self::CONCIERGE_BOUNTY => 'Concierge Bounty',
        };
    }
}
