<?php

namespace App\Enums;

enum VenueStatus: string
{
    case DRAFT = 'draft';

    case UPCOMING = 'upcoming';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UPCOMING => 'Not Yet',
            self::PENDING => 'Soon',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }
}
