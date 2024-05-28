<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EarningType: string implements HasLabel
{
    case RESTAURANT = 'restaurant';
    case PARTNER = 'partner_restaurant';
    case CONCIERGE = 'concierge';
    case CONCIERGE_REFERRAL_1 = 'concierge_referral_1';
    case CONCIERGE_REFERRAL_2 = 'concierge_referral_2';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RESTAURANT => 'Restaurant',
            self::PARTNER => 'Partner Restaurant',
            self::CONCIERGE => 'Concierge',
            self::CONCIERGE_REFERRAL_1 => 'Concierge Referral 1',
            self::CONCIERGE_REFERRAL_2 => 'Concierge Referral 2',
        };
    }
}
