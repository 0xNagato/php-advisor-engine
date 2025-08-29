<?php

namespace App\Enums;

enum VipCodeTemplate: string
{
    case AVAILABILITY_CALENDAR = 'availability_calendar';
    case SINGLE_VENUE = 'single_venue';
    case INFLUENCER = 'influencer';

    /**
     * Get all template values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get template options for forms
     */
    public static function options(): array
    {
        return [
            self::AVAILABILITY_CALENDAR->value => 'Availability Calendar',
            self::SINGLE_VENUE->value => 'Single Venue',
            self::INFLUENCER->value => 'Influencer',
        ];
    }

    /**
     * Get the default template
     */
    public static function default(): self
    {
        return self::AVAILABILITY_CALENDAR;
    }

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::AVAILABILITY_CALENDAR => 'Availability Calendar',
            self::SINGLE_VENUE => 'Single Venue',
            self::INFLUENCER => 'Influencer',
        };
    }
}
