<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EarningType: string implements HasLabel
{
    case VENUE = 'venue';
    case PARTNER = 'partner_venue';
    case CONCIERGE = 'concierge';
    case CONCIERGE_REFERRAL_1 = 'concierge_referral_1';
    case CONCIERGE_REFERRAL_2 = 'concierge_referral_2';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VENUE => 'Venue',
            self::PARTNER => 'Partner Venue',
            self::CONCIERGE => 'Concierge',
            self::CONCIERGE_REFERRAL_1 => 'Concierge Referral 1',
            self::CONCIERGE_REFERRAL_2 => 'Concierge Referral 2',
        };
    }
}
