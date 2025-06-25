# Multi-Platform Booking System - Implementation Refresher

*Last Updated: June 2025*

## Overview

The system was refactored from a tightly-coupled CoverManager integration into a flexible multi-platform architecture that supports both **CoverManager** and **Restoo**.

## 🏗️ Core Architecture

### BookingPlatformInterface Contract

All booking platforms implement this standardized interface located at `app/Contracts/BookingPlatformInterface.php`:

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

### VenuePlatform Model

Location: `app/Models/VenuePlatform.php`

Stores platform-specific configurations:

```php
protected $fillable = [
    'venue_id',
    'platform_type',      // 'covermanager' or 'restoo'
    'is_enabled',         // boolean
    'configuration',      // JSON array with platform-specific config
    'last_synced_at',
];
```

**Helper Methods:**

- `getConfig(string $key, $default = null)` - Get config values
- `setConfig(string $key, $value)` - Set config values

### BookingPlatformFactory

Location: `app/Factories/BookingPlatformFactory.php`

Dynamically resolves the appropriate service based on venue configuration:

```php
public function getPlatformForVenue(Venue $venue): ?BookingPlatformInterface
{
    $platform = $venue->platforms()->where('is_enabled', true)->first();
    
    return match ($platform?->platform_type) {
        'covermanager' => $this->container->make(CoverManagerService::class),
        'restoo' => $this->container->make(RestooService::class),
        default => null,
    };
}
```

## 🔧 Platform Services

### CoverManagerService

**Location:** `app/Services/CoverManagerService.php`  
**Status:** ✅ Working (tested with CoverManager support)

#### Key Discovery

API key must be sent in headers, not URL path (this was the breakthrough!)

#### API Endpoints

- **Restaurant list:** `GET /restaurant/list/{apiKey}/`
- **Availability:** `POST /reserv/availability` (with ApiKey header)
- **Create reservation:** `POST /reserv/reserv` (with ApiKey header)  
- **Cancel reservation:** `POST /reserv/cancel_client` (with ApiKey header)

#### Configuration Structure

```php
// Stored in VenuePlatform.configuration
[
    'restaurant_id' => 'CM_RESTAURANT_ID',
    'api_key' => 'API_KEY_VALUE'
]
```

### RestooService

**Location:** `app/Services/RestooService.php`  
**Status:** ✅ Fully Working (June 2025)

#### Key Features

- Uses partner ID `'prima'`
- Rounds booking times to nearest 15 minutes (Restoo requirement)
- Uses Bearer token authentication
- External booking ID format: `"Prima-{booking_id}"`
- **Real customer data required** - test/fake data is rejected by API

#### Latest API Updates (June 2025)

- **New API Key:** `92518963c1464359a616150021320d97`
- **Cancel endpoint changed:** `POST /api/prima/v3/booking/{uuid}/cancel` (was DELETE)
- **Cancel payload required:** `{"cancelReason": "OTHER|BOOKED_ANOTHER_PLACE|CHANGED_PLANS"}`
- **API URL correction:** `/api/prima/v3/booking` (not `/partners/prima/v3/booking`)

#### Configuration Structure

```php
// Stored in VenuePlatform.configuration (per-venue in database)
[
    'api_key' => '92518963c1464359a616150021320d97', // Updated June 2025
    'account' => 'ACCOUNT_ID'
]
```

#### API Key Lookup Process

RestooService retrieves credentials **per-venue** from the `venue_platforms` table:

```php
// Step 1: Get platform record for venue
$platform = $venue->getPlatform('restoo');

// Step 2: Extract credentials from JSON configuration
$apiKey = $platform->getConfig('api_key');  // '92518963c1464359a616150021320d97'
$account = $platform->getConfig('account');  // 'ACCOUNT_ID'

// Step 3: Use in API calls
Http::withHeaders([
    'Account' => $account,
    'Authorization' => "Bearer {$apiKey}",
])->post($url, $payload);
```

**Key Point:** API keys are stored **per-venue**, not globally. This allows different venues to use different Restoo accounts if needed.

#### API Endpoints

- **Status check:** `GET /partners/prima/v3/status`
- **Availability:** `POST /partners/prima/v3/availability`
- **Create reservation:** `POST /api/prima/v3/booking` ✅ **CORRECTED URL**
- **Cancel reservation:** `POST /api/prima/v3/booking/{id}/cancel` ✅ **UPDATED**

## 🗄️ Database Models

### CoverManagerReservation

**Location:** `app/Models/CoverManagerReservation.php`

Tracks CoverManager reservations with methods:

- `createFromBooking(Booking $booking)` - Creates reservation record
- `syncToCoverManager()` - Syncs to CoverManager API
- `cancelInCoverManager()` - Cancels via CoverManager API

### RestooReservation  

**Location:** `app/Models/RestooReservation.php`

Similar structure to CoverManagerReservation:

- `createFromBooking(Booking $booking)` - Creates reservation record
- `syncToRestoo()` - Syncs to Restoo API
- `cancelInRestoo()` - Cancels via Restoo API ✅ **UPDATED**

**Important:** Uses correct booking property mappings:

- `booking_at` (not `booking_date`)
- `guest_count` (not `party_size`)
- `guest_name` (not `customer_name`)
- `booking.venue` (not `booking.scheduleTemplate.venue`)

## 🎯 Venue Integration

The `Venue` model now has platform management methods:

```php
// Get all platforms for venue
public function platforms(): HasMany

// Get specific platform by type
public function getPlatform(string $platformType): ?VenuePlatform

// Check if platform is enabled
public function hasPlatform(string $platformType): bool

// Get platform service instance
public function getBookingPlatform(): ?BookingPlatformInterface
```

### Backward Compatibility Methods

```php
// Legacy CoverManager methods still work
public function usesCoverManager(): bool
public function coverManager()
```

## 🎧 Event System

### Event Listeners

**Locations:** `app/Listeners/`

1. **BookingPlatformSyncListener** - Syncs confirmed bookings to all enabled platforms
2. **BookingPlatformCancellationListener** - Cancels reservations on all platforms when bookings are cancelled ✅ **UPDATED**

### ⚠️ Critical Event System Notes (June 2025)

**Auto-Discovery vs Explicit Registration:**

The system uses Laravel's auto-discovery (`shouldDiscoverEvents(): bool` returns `true`) to automatically find and register event listeners. This means:

- ✅ **DO NOT** explicitly register listeners in `EventServiceProvider.php` if using auto-discovery
- ✅ **Old listeners must be deleted** from filesystem to prevent duplicate registrations
- ✅ **Removed files:** `CoverManagerBookingListener.php`, `CoverManagerBookingCancellationListener.php`

**Event Flow:**

```php
// Booking confirmation (API)
BookingController::complete() → {
    if (prime) → CompleteBooking::run() → BookingConfirmed::dispatch()
    else → BookingService::processBooking() → BookingConfirmed::dispatch()
}

// Booking cancellation  
ViewBooking::cancelNonPrimeBooking() → BookingCancelled::dispatch()
ViewBooking::processRefund() (full refund only) → BookingCancelled::dispatch()
ListVenues::bulkEditBookings() → BookingCancelled::dispatch()
```

### API Booking Flow

**Location:** `app/Http/Controllers/Api/BookingController.php`

The API has separate flows for prime vs non-prime bookings:

```php
// Prime bookings
POST /api/bookings/{id}/complete → CompleteBooking::run() → BookingConfirmed

// Non-prime bookings  
POST /api/bookings/{id}/complete → BookingService::processBooking() → BookingConfirmed
```

**Authentication:** All API endpoints require Sanctum authentication (`auth:sanctum` middleware).

## 📊 Migration & Backward Compatibility

### Migration Path

Migration: `database/migrations/2025_05_20_221353_migrate_covermanager_venues_to_venue_platforms.php`

Converts old venue-level CoverManager fields to new `VenuePlatform` records:

- `uses_covermanager` → `VenuePlatform` with `platform_type: 'covermanager'`
- `covermanager_id` → stored in `configuration.restaurant_id`
- `covermanager_sync_enabled` → `is_enabled` field

### Booking Model Integration

**Location:** `app/Models/Booking.php`

The Booking model has methods for both systems:

```php
// New multi-platform method
public function syncToBookingPlatforms(): bool

// Legacy methods (still work)
public function syncToCoverManager(): bool
public function syncToRestoo(): bool
```

## 🔄 Service Provider Registration

**Location:** `app/Providers/BookingPlatformServiceProvider.php`

Registers the factory and default implementations:

```php
$this->app->singleton(BookingPlatformFactory::class);
$this->app->bind(BookingPlatformInterface::class, CoverManagerService::class);
```

## 📝 Usage Examples

### Setting up a venue with CoverManager

```php
$venue = Venue::find(1);

// Create platform configuration
$venue->platforms()->create([
    'platform_type' => 'covermanager',
    'is_enabled' => true,
    'configuration' => [
        'restaurant_id' => 'CM_REST_123',
        'api_key' => 'api_key_here'
    ]
]);
```

### Setting up a venue with Restoo (Updated June 2025)

```php
$venue = Venue::find(1);

// Create platform configuration with new API key
$venue->platforms()->create([
    'platform_type' => 'restoo',
    'is_enabled' => true,
    'configuration' => [
        'api_key' => '92518963c1464359a616150021320d97', // New API key
        'account' => 'ACCOUNT_ID_HERE'
    ]
]);
```

### Checking if venue uses a platform

```php
$venue = Venue::find(1);

if ($venue->hasPlatform('covermanager')) {
    $service = $venue->getBookingPlatform();
    $available = $service->checkAvailability($venue, $date, $time, $partySize);
}
```

### Creating reservations

```php
// This automatically syncs to all enabled platforms
$booking = Booking::create([...]);
$booking->syncToBookingPlatforms();
```

## 🚨 Important Notes

1. **CoverManager API Key Location:** Must be in headers, not URL path
2. **Restoo Time Rounding:** All times must be rounded to nearest 15 minutes
3. **Restoo Cancel Endpoint:** Now uses POST with payload (updated June 2025)
4. **Restoo New API Key:** `92518963c1464359a616150021320d97` (replaces previous key)
5. **Restoo API URL:** `/api/prima/v3/booking` (not `/partners/prima/v3/booking`)
6. **Real Data Required:** Restoo rejects test/fake customer data - use real names, emails, phones
7. **Error Handling:** Both services have comprehensive logging and retry logic
8. **Testing:** CoverManager endpoints are confirmed working with their support team
9. **Event Auto-Discovery:** Old listener files must be deleted to prevent duplicates
10. **API Authentication:** All booking API endpoints require Sanctum tokens

## 🔄 June 2025 Restoo Updates

### Action Items

- [x] ~~Update existing venues using Restoo with new API key~~ ✅ Completed
- [x] ~~Test cancellation functionality with new endpoint~~ ✅ Working
- [x] ~~Fix API URL path~~ ✅ Corrected to `/api/prima/v3/booking`
- [x] ~~Fix booking property mappings in RestooReservation model~~ ✅ Fixed
- [x] ~~Remove duplicate event listeners~~ ✅ Cleaned up
- [ ] Consider making `cancelReason` configurable based on booking context

### Updated Cancel Reasons

Restoo now supports these cancel reasons:

- `"BOOKED_ANOTHER_PLACE"` - Customer booked elsewhere
- `"CHANGED_PLANS"` - Customer changed their plans  
- `"OTHER"` - Default/other reason

## 📋 TODO/Future Enhancements

- [x] ~~Complete Restoo testing and validation~~ ✅ Updated June 2025
- [x] ~~Update Restoo cancellation endpoint~~ ✅ Completed
- [x] ~~Fix event listener duplicates~~ ✅ Cleaned up
- [x] ~~Implement API booking sync~~ ✅ Working for both prime/non-prime
- [ ] Add more booking platforms (OpenTable, Resy, etc.)
- [ ] Implement availability sync from external platforms back to PRIMA
- [ ] Add platform-specific booking modifications
- [ ] Create admin interface for managing platform configurations
- [ ] Make Restoo cancelReason configurable

## 🔍 Key Files for Reference

### Core Files

- `app/Contracts/BookingPlatformInterface.php` - Interface contract
- `app/Factories/BookingPlatformFactory.php` - Service factory
- `app/Models/VenuePlatform.php` - Platform configuration model

### Services

- `app/Services/CoverManagerService.php` - CoverManager API integration
- `app/Services/RestooService.php` - Restoo API integration ✅ **UPDATED**

### Models

- `app/Models/CoverManagerReservation.php` - CoverManager reservations
- `app/Models/RestooReservation.php` - Restoo reservations

### Event Handling

- `app/Listeners/BookingPlatformSyncListener.php` - Multi-platform sync
- `app/Listeners/BookingPlatformCancellationListener.php` - Multi-platform cancellation ✅ **UPDATED**

### API Controllers

- `app/Http/Controllers/Api/BookingController.php` - API booking endpoints ✅ **DOCUMENTED**

### Documentation

- `docs/booking_platforms.md` - Detailed architecture documentation
- `docs/covermanager_integration.md` - CoverManager-specific docs

---

*This refresher provides a comprehensive overview of the multi-platform booking system implementation. Refer to individual files for detailed implementation specifics.*
