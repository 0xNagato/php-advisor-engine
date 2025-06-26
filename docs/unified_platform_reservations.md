# Unified Platform Reservations Architecture

## Overview

As of June 2025, PRIMA has migrated to a **unified platform reservation system** that consolidates all external booking platform integrations into a single, scalable architecture. This replaces the previous platform-specific models with a unified approach.

## Architecture Benefits

✅ **Single Source of Truth**: All platform reservations stored in one table  
✅ **Easier Maintenance**: One set of methods for all platforms  
✅ **Better Scalability**: Adding new platforms requires minimal code changes  
✅ **Unified Reporting**: Query across all platforms with consistent interface  
✅ **Type Safety**: Consistent API for all platform operations  
✅ **Data Integrity**: Platform-specific fields stored in structured JSON  

## Core Model: PlatformReservation

**Location:** `app/Models/PlatformReservation.php`

### Database Schema

```sql
CREATE TABLE platform_reservations (
    id BIGSERIAL PRIMARY KEY,
    venue_id BIGINT NOT NULL REFERENCES venues(id),
    booking_id BIGINT NOT NULL REFERENCES bookings(id),
    platform_type VARCHAR(255) NOT NULL,           -- 'covermanager', 'restoo', etc.
    platform_reservation_id VARCHAR(255) NULL,     -- External platform's ID
    platform_status VARCHAR(255) NULL,             -- Status from external platform
    synced_to_platform BOOLEAN DEFAULT FALSE,      -- Sync status
    last_synced_at TIMESTAMP NULL,                 -- Last sync time
    platform_data JSON NULL,                       -- Platform-specific fields
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Key Fields

| Field | Purpose | Example |
|-------|---------|---------|
| `platform_type` | Identifies the platform | `'covermanager'`, `'restoo'` |
| `platform_reservation_id` | External platform's reservation ID | CoverManager's `id_reserv` |
| `platform_status` | Status from external platform | `'confirmed'`, `'cancelled'` |
| `synced_to_platform` | Whether successfully synced | `true`/`false` |
| `platform_data` | JSON field for platform-specific data | See examples below |

### Platform-Specific Data Examples

**CoverManager:**

```json
{
    "reservation_date": "2025-06-26",
    "reservation_time": "19:30:00",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "+1234567890",
    "party_size": 4,
    "notes": "Anniversary dinner",
    "covermanager_response": {...}
}
```

**Restoo:**

```json
{
    "reservation_datetime": "2025-06-26T19:30:00.000Z",
    "customer_name": "John Doe",
    "customer_email": "john@example.com", 
    "customer_phone": "+1234567890",
    "party_size": 4,
    "notes": "Anniversary dinner",
    "restoo_response": {...}
}
```

## Unified Methods

### Creating Reservations

```php
// Create reservation for specific platform
$reservation = PlatformReservation::createFromBooking($booking, 'covermanager');
$reservation = PlatformReservation::createFromBooking($booking, 'restoo');

// This calls the appropriate platform API and creates the DB record
```

### Syncing Reservations

```php
// Sync to platform (for retry scenarios)
$success = $reservation->syncToPlatform();

// This calls the appropriate sync method based on platform_type
```

### Cancelling Reservations

```php
// Cancel in external platform
$success = $reservation->cancelInPlatform();

// This calls the appropriate cancel method and updates status
```

## Duplicate Handling & Smart Cancellation

### Duplicate Detection

PRIMA automatically detects duplicate reservations when external platforms return the same reservation ID for multiple booking attempts. This commonly happens when:

- Customers submit the same booking multiple times
- Network issues cause retry attempts
- Platform APIs return existing reservations instead of creating new ones

**How it works:**

1. **API Call**: PRIMA calls the platform API to create a reservation
2. **Duplicate Check**: If the returned reservation ID already exists in our database, it's flagged as a duplicate
3. **Unique Storage**: The duplicate is stored with a modified ID: `{original_id}_dup_{booking_id}`
4. **Metadata Tracking**: Full relationship data is stored to track which bookings share the same platform reservation

**Example duplicate record:**

```json
{
    "platform_reservation_id": "cm_12345_dup_67890",
    "platform_data": {
        "is_duplicate": true,
        "original_platform_reservation_id": "cm_12345",
        "linked_to_booking_id": 12345,
        "customer_name": "John Doe",
        // ... other platform data
    }
}
```

### Smart Cancellation Logic

PRIMA uses intelligent cancellation logic that minimizes unnecessary API calls to external platforms:

#### For Duplicate Reservations

**When cancelling a duplicate reservation:**

- ✅ **Local cancellation only** - Updates the duplicate record status to 'cancelled'
- ❌ **No API call** - Does not call the external platform (since it shares the same reservation with other bookings)
- 🔍 **Original check** - Only calls the platform API if this is the last active booking for that reservation

#### For Original Reservations

**When cancelling an original reservation:**

- ✅ **Platform API call** - Calls the external platform to cancel the reservation
- 🔄 **Auto-cascade** - Automatically marks all related duplicate reservations as 'cancelled'
- 📝 **Status sync** - Updates all related records to maintain consistency

#### Cancellation Decision Tree

```
Is this a duplicate reservation?
├─ YES: 
│  ├─ Are there other active bookings for this reservation?
│  │  ├─ YES: Cancel locally only (no API call)
│  │  └─ NO: Cancel in platform + mark all related as cancelled
│  └─ Update local status to 'cancelled'
└─ NO (original reservation):
   ├─ Call platform API to cancel
   ├─ Find all duplicate reservations with same platform_reservation_id
   ├─ Mark all duplicates as 'cancelled'
   └─ Update local status to 'cancelled'
```

### Implementation Details

**Duplicate creation logic:**

```php
// Check if reservation ID already exists
$existingReservation = self::where('platform_type', 'covermanager')
    ->where('platform_reservation_id', $response['id_reserv'])
    ->first();

if ($existingReservation) {
    // Create duplicate with unique ID
    $reservation = self::create([
        'platform_reservation_id' => $response['id_reserv'] . '_dup_' . $booking->id,
        'platform_data' => [
            'is_duplicate' => true,
            'original_platform_reservation_id' => $response['id_reserv'],
            'linked_to_booking_id' => $existingReservation->booking_id,
            // ... other data
        ]
    ]);
}
```

**Smart cancellation logic:**

```php
public function cancelInPlatform(): bool
{
    $isDuplicate = $this->platform_data['is_duplicate'] ?? false;
    
    if ($isDuplicate) {
        // For duplicates, check if other active reservations exist
        $originalId = $this->platform_data['original_platform_reservation_id'];
        $activeCount = self::where('platform_type', $this->platform_type)
            ->where(function ($query) use ($originalId) {
                $query->where('platform_reservation_id', $originalId)
                      ->orWhere('platform_data->original_platform_reservation_id', $originalId);
            })
            ->where('platform_status', '!=', 'cancelled')
            ->where('id', '!=', $this->id)
            ->count();
            
        if ($activeCount > 0) {
            // Other active reservations exist, cancel locally only
            $this->update(['platform_status' => 'cancelled']);
            return true;
        }
        
        // This is the last active reservation, cancel in platform
        $reservationIdToCancel = $originalId;
    } else {
        // Original reservation, cancel in platform
        $reservationIdToCancel = $this->platform_reservation_id;
    }
    
    // Call platform API
    $success = $this->cancelInCoverManager($reservationIdToCancel);
    
    if ($success) {
        // Auto-cancel all related duplicates
        self::where('platform_type', $this->platform_type)
            ->where(function ($query) use ($reservationIdToCancel) {
                $query->where('platform_reservation_id', $reservationIdToCancel)
                      ->orWhere('platform_data->original_platform_reservation_id', $reservationIdToCancel);
            })
            ->update(['platform_status' => 'cancelled']);
    }
    
    return $success;
}
```

### Benefits

1. **Reduced API Calls**: Avoids unnecessary cancellation calls when multiple bookings share the same platform reservation
2. **Data Consistency**: Ensures all related records are properly updated when any booking is cancelled
3. **Platform Reliability**: Reduces load on external platform APIs
4. **Audit Trail**: Maintains full visibility into which bookings share platform reservations
5. **Error Resilience**: Handles edge cases where platforms return unexpected duplicate IDs

## Venue Relationships

### New Unified Relationship

```php
// Get all platform reservations for venue
$venue->platformReservations()

// Filter by platform type
$venue->platformReservations()->where('platform_type', 'covermanager')
```

### Backward Compatible Relationships

```php
// These still work and filter by platform_type internally
$venue->coverManagerReservations()  // platform_type = 'covermanager'
$venue->restooReservations()        // platform_type = 'restoo'
```

## Event System Integration

### Unified Event Listeners

**BookingPlatformSyncListener** (`app/Listeners/BookingPlatformSyncListener.php`)

- Listens for `BookingConfirmed` events
- Creates `PlatformReservation` records for all enabled platforms
- Handles retry logic and error handling

**BookingPlatformCancellationListener** (`app/Listeners/BookingPlatformCancellationListener.php`)

- Listens for `BookingCancelled` events  
- Finds and cancels reservations across all platforms
- Handles platform-specific cancellation logic

### Event Flow

```
BookingConfirmed → BookingPlatformSyncListener → PlatformReservation::createFromBooking()
BookingCancelled → BookingPlatformCancellationListener → PlatformReservation::cancelInPlatform()
```

## Platform-Specific Implementation

### CoverManager Integration

```php
protected static function createCoverManagerReservation(Booking $booking): ?self
{
    // Get venue platform configuration
    $platform = $venue->getPlatform('covermanager');
    $restaurantId = $platform->getConfig('restaurant_id');
    
    // Call CoverManager API
    $response = $coverManagerService->createReservationRaw($restaurantId, $bookingData);
    
    // Create unified record
    return self::create([
        'platform_type' => 'covermanager',
        'platform_reservation_id' => $response['id_reserv'],
        'platform_data' => [...] // CoverManager-specific fields
    ]);
}
```

### Restoo Integration

```php
protected static function createRestooReservation(Booking $booking): ?self
{
    // Call Restoo API
    $response = $restooService->createReservation($venue, $booking);
    
    // Create unified record
    return self::create([
        'platform_type' => 'restoo', 
        'platform_reservation_id' => $response['uuid'],
        'platform_data' => [...] // Restoo-specific fields
    ]);
}
```

## Migration Path

### Database Migration

**Step 1:** Create unified table

```bash
php artisan migrate --path=database/migrations/2025_06_26_165751_create_platform_reservations_table.php
```

**Step 2:** Migrate existing data

```bash
php artisan migrate --path=database/migrations/2025_06_26_170117_migrate_existing_reservations_to_platform_reservations.php
```

### Data Migration Details

The migration automatically transfers:

- `cover_manager_reservations` → `platform_reservations` (platform_type: 'covermanager')
- `restoo_reservations` → `platform_reservations` (platform_type: 'restoo')

All existing data is preserved in the `platform_data` JSON field.

## Filament Admin Integration

### Updated Resources

**BookingPlatformsResource** (`app/Filament/Resources/Venue/BookingPlatformsResource.php`)

- Shows unified reservation counts per platform
- Uses `VenuePlatform` model as base
- Each platform connection appears as separate row

**ViewReservations Page** (`app/Filament/Resources/Venue/BookingPlatformsResource/Pages/ViewReservations.php`)

- Displays reservations for specific platform type
- Adapts columns based on platform (CoverManager shows separate date/time, Restoo shows datetime)
- Clickable rows navigate to booking details

### Reservation Count Display

```php
TextColumn::make('reservations_count')
    ->getStateUsing(function (VenuePlatform $record): int {
        return $record->venue->platformReservations()
            ->where('platform_type', $record->platform_type)
            ->count();
    })
```

## Adding New Platforms

### 1. Extend PlatformReservation Model

```php
public static function createFromBooking(Booking $booking, string $platformType): ?self
{
    return match ($platformType) {
        'covermanager' => self::createCoverManagerReservation($booking),
        'restoo' => self::createRestooReservation($booking),
        'opentable' => self::createOpenTableReservation($booking), // NEW
        default => null,
    };
}
```

### 2. Add Platform-Specific Methods

```php
protected static function createOpenTableReservation(Booking $booking): ?self
{
    // Platform-specific implementation
    $response = $openTableService->createReservation($venue, $booking);
    
    return self::create([
        'platform_type' => 'opentable',
        'platform_reservation_id' => $response['reservation_id'],
        'platform_data' => [
            // OpenTable-specific fields
        ]
    ]);
}
```

### 3. Update Event Listeners

```php
switch ($platform->platform_type) {
    case 'covermanager':
        $success = $this->syncToCoverManager($booking);
        break;
    case 'restoo':
        $success = $this->syncToRestoo($booking);
        break;
    case 'opentable': // NEW
        $success = $this->syncToOpenTable($booking);
        break;
}
```

## Backward Compatibility

### Legacy Model Support

During the transition period, the old models (`CoverManagerReservation`, `RestooReservation`) are still available but deprecated:

```php
// OLD (deprecated)
$reservation = CoverManagerReservation::createFromBooking($booking);

// NEW (recommended)
$reservation = PlatformReservation::createFromBooking($booking, 'covermanager');
```

### Migration Timeline

1. **Pre-Migration**: Both old and new models work
2. **Migration**: Data transferred to unified table
3. **Post-Migration**: Old models can be safely deleted

## Testing

### Comprehensive Test Suite

**Location:** `tests/Unit/Models/PlatformReservationTest.php`

The unified platform reservation system includes comprehensive Pest tests covering:

**Creation Tests:**

- ✅ Normal reservation creation when no duplicates exist
- ✅ Duplicate reservation handling when platforms return existing IDs
- ✅ Proper metadata storage for duplicate relationships

**Cancellation Tests:**

- ✅ Normal reservation cancellation in external platforms
- ✅ Local-only cancellation for duplicates when originals are active
- ✅ Platform cancellation when cancelling the last active reservation
- ✅ Auto-cascade cancellation of all related duplicates
- ✅ Correct reservation ID usage for platform API calls

**Edge Case Tests:**

- ✅ Missing platform data handling
- ✅ Venues without platform configuration
- ✅ Platform API failures and null responses
- ✅ Platform API error responses

**Run the tests:**

```bash
php artisan test tests/Unit/Models/PlatformReservationTest.php
```

### Verify Migration

```php
// Check data was migrated correctly
$oldCount = DB::table('cover_manager_reservations')->count() + 
           DB::table('restoo_reservations')->count();
$newCount = PlatformReservation::count();

// Should be equal
assert($oldCount === $newCount);
```

### Test Unified Methods

```php
// Test creation
$reservation = PlatformReservation::createFromBooking($booking, 'covermanager');
assert($reservation->platform_type === 'covermanager');

// Test sync
$success = $reservation->syncToPlatform();
assert($success === true);

// Test cancellation
$cancelled = $reservation->cancelInPlatform();
assert($cancelled === true);
```

### Test Duplicate Handling

```php
// Test duplicate detection
$firstBooking = Booking::factory()->create();
$firstReservation = PlatformReservation::createFromBooking($firstBooking, 'covermanager');

// Mock platform returning same ID
$secondBooking = Booking::factory()->create();
$secondReservation = PlatformReservation::createFromBooking($secondBooking, 'covermanager');

// Verify duplicate handling
assert($secondReservation->platform_data['is_duplicate'] === true);
assert($secondReservation->platform_data['linked_to_booking_id'] === $firstBooking->id);

// Test smart cancellation
$result = $secondReservation->cancelInPlatform(); // Should cancel locally only
assert($result === true);
assert($firstReservation->fresh()->platform_status !== 'cancelled'); // Original still active
```

## Monitoring & Logging

### Unified Logging Format

All platform operations now use consistent logging:

```
CoverManager create reservation successful for booking 12345 at Venue Name
Restoo sync reservation failed for booking 12346 at Venue Name: API error
```

### Key Metrics to Monitor

- Reservation creation success rates by platform
- Sync failure rates and reasons
- Cancellation success rates
- Platform-specific error patterns

## Best Practices

### 1. Always Use Unified Model for New Code

```php
// ✅ Good
$reservation = PlatformReservation::createFromBooking($booking, $platformType);

// ❌ Avoid (deprecated)
$reservation = CoverManagerReservation::createFromBooking($booking);
```

### 2. Handle Platform-Specific Data Properly

```php
// ✅ Good - Check platform type before accessing specific data
if ($reservation->platform_type === 'covermanager') {
    $date = $reservation->platform_data['reservation_date'];
    $time = $reservation->platform_data['reservation_time'];
} elseif ($reservation->platform_type === 'restoo') {
    $datetime = $reservation->platform_data['reservation_datetime'];
}
```

### 3. Use Scopes for Filtering

```php
// ✅ Good - Use scopes
$coverManagerReservations = PlatformReservation::coverManager()->get();
$restooReservations = PlatformReservation::restoo()->get();

// ✅ Also good - Explicit filtering
$platformReservations = PlatformReservation::forPlatform('covermanager')->get();
```

### 4. Error Handling

```php
try {
    $reservation = PlatformReservation::createFromBooking($booking, $platformType);
    if (!$reservation) {
        Log::error("Failed to create {$platformType} reservation for booking {$booking->id}");
        return false;
    }
} catch (Exception $e) {
    Log::error("Exception creating {$platformType} reservation: " . $e->getMessage());
    return false;
}
```

## Troubleshooting

### Common Issues

1. **Platform data not found**: Check that `platform_data` contains expected fields
2. **Sync failures**: Verify platform configuration in `VenuePlatform` table
3. **Missing reservations**: Check that event listeners are properly registered

### Debugging Commands

```bash
# Check platform configurations
php artisan tinker --execute="VenuePlatform::with('venue')->get()->each(fn(\$p) => echo \$p->venue->name . ' - ' . \$p->platform_type);"

# Check reservation counts
php artisan tinker --execute="echo 'Total: ' . PlatformReservation::count(); echo 'CoverManager: ' . PlatformReservation::coverManager()->count(); echo 'Restoo: ' . PlatformReservation::restoo()->count();"

# Verify migration
php artisan tinker --execute="echo 'Old tables: ' . (DB::table('cover_manager_reservations')->count() + DB::table('restoo_reservations')->count()); echo 'New table: ' . PlatformReservation::count();"
```

---

*This unified architecture provides a solid foundation for scaling PRIMA's booking platform integrations while maintaining backward compatibility and data integrity.*
