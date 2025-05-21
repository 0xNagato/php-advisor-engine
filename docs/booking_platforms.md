# Multi-Platform Booking System

## Overview

This document describes the architecture and implementation of PRIMA's multi-platform booking system, which allows venues to integrate with multiple external booking platforms like CoverManager and Restoo.

## The Challenge

The initial implementation had CoverManager integration tightly coupled with the Venue model, using hard-coded fields. We needed a more flexible system that could:

1. Support multiple booking platforms
2. Allow easy addition of new platforms
3. Maintain backward compatibility
4. Provide a consistent admin interface

## Architecture

### Core Components

#### 1. BookingPlatformInterface

A contract defining standard methods all booking platforms must implement:

```php
interface BookingPlatformInterface
{
    public function checkAuth(Venue $venue): bool;
    public function checkAvailability(Venue $venue, Carbon $date, string $time, int $partySize): array;
    public function createReservation(Venue $venue, Booking $booking): ?array;
    public function cancelReservation(Venue $venue, string $externalReservationId): bool;
    public function getPlatformName(): string;
}
```

#### 2. VenuePlatform Model

A dedicated model to store platform-specific configurations:

```php
class VenuePlatform extends Model
{
    protected $fillable = [
        'venue_id',
        'platform_type',
        'is_enabled',
        'configuration',
        'last_synced_at',
    ];
    
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }
    
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
```

#### 3. Platform Services

Implementations of the BookingPlatformInterface:

- `CoverManagerService` - Integration with CoverManager
- `RestooService` - Integration with Restoo

#### 4. BookingPlatformFactory

A service to instantiate the correct platform service:

```php
class BookingPlatformFactory
{
    public function getPlatformForVenue(Venue $venue): ?BookingPlatformInterface
    {
        $platform = $venue->platforms()
            ->where('is_enabled', true)
            ->first();

        if (!$platform) {
            return null;
        }

        return match ($platform->platform_type) {
            'covermanager' => $this->container->make(CoverManagerService::class),
            'restoo' => $this->container->make(RestooService::class),
            default => null,
        };
    }
}
```

### Venue Model Extensions

The Venue model was extended with methods to work with the new platform system:

```php
// In Venue model
public function platforms(): HasMany
{
    return $this->hasMany(VenuePlatform::class);
}

public function getPlatform(string $platformType): ?VenuePlatform
{
    return $this->platforms()->where('platform_type', $platformType)->first();
}

public function hasPlatform(string $platformType): bool
{
    return $this->platforms()->where('platform_type', $platformType)
        ->where('is_enabled', true)->exists();
}

public function getBookingPlatform()
{
    $factory = app(BookingPlatformFactory::class);
    return $factory->getPlatformForVenue($this);
}
```

## Admin Interface

The `BookingPlatformsResource` provides a Filament admin interface to:

1. View all venue-platform connections
2. Create new connections between venues and platforms
3. Test platform credentials
4. Edit existing connections
5. Sync venue data with platforms
6. Enable/disable platform connections

### Key Components

- `ListPlatforms` - Table view of all venue-platform connections
- `CreatePlatform` - Form to create new platform connections with credential testing
- `EditPlatform` - Form to edit existing connections

## Database Schema

```sql
CREATE TABLE venue_platforms (
    id BIGINT PRIMARY KEY,
    venue_id BIGINT NOT NULL,
    platform_type VARCHAR(255) NOT NULL,
    is_enabled BOOLEAN DEFAULT true,
    configuration JSON NULL,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_venue_platforms_venue_id FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    UNIQUE (venue_id, platform_type)
);
```

## Migration Strategy

A migration script was created to:

1. Create the new `venue_platforms` table
2. Migrate existing CoverManager venues to the new structure
3. Keep old columns temporarily for backward compatibility

## Key Benefits

1. **Decoupling**: Venue model no longer has platform-specific fields
2. **Flexibility**: Support for multiple booking platforms per venue
3. **Extensibility**: Easy to add new booking platforms
4. **Improved Admin Interface**: Dedicated UI for managing platform connections
5. **Consistent API**: All platforms conform to the same interface

## Adding New Platforms

To add a new booking platform:

1. Create a new service class implementing `BookingPlatformInterface`
2. Add the new platform type to the `BookingPlatformFactory`
3. Update the platform selection dropdown in the Filament resource
4. Create any platform-specific configuration fields

## Future Improvements

1. Implement webhook handlers for real-time platform updates
2. Create a unified dashboard for all platform bookings
3. Add platform-specific analytics
4. Implement automatic failover between platforms
