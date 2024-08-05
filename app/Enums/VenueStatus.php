<?php

namespace App\Enums;

enum VenueStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Not Yet',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }
}
