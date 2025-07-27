# CoverManager Availability Sync Implementation

*Created: July 26, 2025 | Updated: July 27, 2025*

## Overview

This document outlines the **completed implementation** of automated CoverManager availability synchronization using a bulk calendar API approach. The system creates venue time slot overrides only when CoverManager availability differs from schedule template defaults, ensuring optimal performance and accurate prime/non-prime settings.

## Requirements

### Primary Functionality ✅ **COMPLETED**
1. **Smart Override Logic**: Only creates venue time slots when CoverManager availability differs from template defaults:
   - **Template = Non-Prime + NO CM availability** → Override to Prime (customer pays upfront)
   - **Template = Prime + HAS CM availability** → Override to Non-Prime (customer pays at restaurant)
   - **Template matches CM availability** → No override needed (uses template default)

2. **Bulk Calendar API**: Uses `/reserv/availability_calendar_total` for efficient single-call processing

3. **Human Override Protection**: Respects all manual changes made via Schedule Manager

4. **Force Booking**: Implements `/reserv/force` endpoint for bypassing availability checks

5. **Comprehensive Testing**: Automated tests with mocked responses and real API validation

### Technical Requirements
- Use existing VenuePlatform architecture (not direct venue fields)
- Respect human-created overrides via activity log detection  
- 7-day sync window by default
- Graceful error handling for API failures
- Activity logging for audit trail

## Architecture Analysis

### Existing Infrastructure ✅
- **VenuePlatform Model**: `app/Models/VenuePlatform.php` with JSON configuration storage
- **CoverManagerService**: `app/Services/CoverManagerService.php` with API integration
- **BookingPlatformInterface**: Contract for all platform services
- **Admin UI**: `app/Filament/Pages/CoverManagerAvailability.php` for testing
- **Command Structure**: Base sync command infrastructure exists
- **Activity Logging**: `spatie/laravel-activitylog` for tracking changes

### Models and Relationships
```php
// VenuePlatform configuration structure
[
    'venue_id' => 123,
    'platform_type' => 'covermanager',
    'is_enabled' => true,
    'configuration' => [
        'restaurant_id' => 'CM_RESTAURANT_ID',
        'api_key' => 'API_KEY_VALUE'
    ]
]

// Venue -> Platform relationship
$platform = $venue->getPlatform('covermanager');
$restaurantId = $platform->getConfig('restaurant_id');
```

### Key Detection Logic
```php
// Human override detection via activity logs
$isHumanCreated = Activity::where('subject_type', Venue::class)
    ->where('subject_id', $venue->id)
    ->where('description', 'override_update')
    ->whereJsonContains('properties->venue_time_slot_id', $venueTimeSlot->id)
    ->exists();
```

## Implementation Plan

### Phase 1: Core Sync Logic ✅ **COMPLETED**

#### 1.1 Add Force Booking to Interface ✅ **DONE**
- **File**: `app/Contracts/BookingPlatformInterface.php`
- **Action**: Added `createReservationForce(Venue $venue, Booking $booking): ?array` method
- **Purpose**: Support bypassing availability checks

#### 1.2 Implement Force Booking in CoverManagerService ✅ **DONE**
- **File**: `app/Services/CoverManagerService.php`
- **Action**: Implemented `createReservationForce()` using `/reserv/force` endpoint
- **Headers**: Uses ApiKey header (not URL path) per existing pattern

#### 1.3 Add Bulk Sync Method to Venue Model ✅ **DONE**
- **File**: `app/Models/Venue.php` 
- **Method**: `syncCoverManagerAvailability(Carbon $date, int $days = 1): bool`
- **Improved Logic**:
  1. Check if venue has enabled CoverManager platform
  2. Make **single bulk API call** using `checkAvailabilityCalendar()` for entire date range
  3. For each schedule template + date combination:
     - Skip if human-created override exists
     - Compare CoverManager availability with template default
     - **Only create override if different** from template prime_time setting
     - Remove unnecessary overrides that match template defaults
     - Log activity for audit trail
- **Performance**: One API call instead of hundreds of individual calls

#### 1.4 Helper Methods ✅ **DONE**
- **File**: `app/Models/Venue.php`
- **Methods**: 
  - `isHumanCreatedSlot(VenueTimeSlot $slot): bool` - Check activity logs for human actions
  - `parseAvailabilityResponse(array $response, ScheduleTemplate $template): bool` - Parse individual API response (legacy)
  - `parseCalendarAvailabilityResponse(array $response, string $dateKey, ScheduleTemplate $template): bool` - Parse bulk calendar response

### Phase 2: Command Implementation ✅ **COMPLETED**

#### 2.1 Existing Sync Command ✅ **WORKING**
- **File**: `app/Console/Commands/SyncCoverManagerAvailability.php`
- **Signature**: `app:sync-covermanager-availability {--venue-id=} {--days=7}`
- **Status**: Existing command works with updated Venue method (backward compatible)
- **Note**: Command still uses legacy venue fields, but Venue model supports both architectures

#### 2.2 Test Command ✅ **CREATED**
- **File**: `app/Console/Commands/TestCoverManagerSync.php`
- **Signature**: `test:covermanager-sync {--create-venue} {--test-api} {--test-sync} {--test-force}`
- **Purpose**: Manual testing and verification of CoverManager integration

### Phase 3: Comprehensive Testing ✅ **COMPLETED**

#### 3.1 Automated Tests (Mocked) ✅ **DONE**
- **File**: `tests/Feature/CoverManagerAvailabilitySyncTest.php`
- **Test Cases**: ✅ All implemented
  1. `test_sync_creates_prime_venue_time_slot_when_no_cm_availability()`
  2. `test_sync_creates_non_prime_venue_time_slot_when_cm_has_availability()`
  3. `test_sync_skips_human_created_venue_time_slots()`
  4. `test_sync_updates_existing_automated_venue_time_slots()`
  5. `test_sync_handles_cm_api_errors_gracefully()`
  6. `test_sync_handles_empty_api_response_gracefully()`
  7. `test_sync_does_not_run_for_disabled_venues()`
  8. `test_sync_does_not_run_for_venues_without_covermanager_platform()`
  9. `test_sync_processes_multiple_days()`

#### 3.2 Real API Tests (Deletable) ✅ **DONE**
- **File**: `tests/Manual/CoverManagerRealApiTest.php`
- **Purpose**: ✅ Verified against real CoverManager API endpoints
- **Status**: Proven working with `prima-test` restaurant
- **Test Cases**: ✅ All working
  1. Real restaurant list retrieval
  2. Real availability checks for party size 2 (confirmed working)
  3. Real force booking functionality (confirmed working)
  4. Real venue sync with actual data (231 VenueTimeSlots created)

#### 3.3 Production Verification ✅ **DONE**
- **Venue**: Playa Soleil (ID: 192)
- **Restaurant**: `prima-test`
- **Results**: 231 VenueTimeSlots created correctly
- **Pattern Verified**:
  - Party size 2 during lunch (1:30pm-3:30pm): NON-PRIME ✅
  - All other combinations: PRIME ✅
- **Full Coverage**: 1pm-11pm (21 time slots × 11 party sizes)

### Phase 4: Performance Optimization ✅ **COMPLETED**

#### 4.1 Bulk API Migration ✅ **DONE**
- **Migration**: Switched from individual `checkAvailability` calls to bulk `checkAvailabilityCalendar`
- **Performance Improvement**: 40 API calls instead of 6,000+ for 5 venues over 7 days
- **Logic Refinement**: Only create venue time slots when overrides are actually needed

#### 4.2 Production Performance Stats ✅ **VERIFIED**
- **Total Schedule Templates**: 18,480 across all CoverManager venues
- **Override Rate**: 14.6% (2,707 venue time slots created)
- **Override Breakdown**:
  - **Non-Prime → Prime**: 2,204 overrides (81.4%) - CoverManager lacks availability
  - **Prime → Non-Prime**: 503 overrides (18.6%) - CoverManager has availability
- **Template Defaults**: 86% non-prime, 14% prime
- **Clean Logic**: Zero erroneous "same as template" overrides

### Phase 5: Documentation & Validation ✅ **COMPLETED**

#### 5.1 Update Documentation ✅ **DONE**
- **Files**: 
  - ✅ `docs/covermanager_availability_sync_implementation.md` - Updated with bulk API and performance stats
  - ✅ `CLAUDE.md` - Added critical data recovery rule and updated sync approach
- **Content**: Implementation details, performance metrics, business logic explanation

#### 5.2 Final Testing ✅ **DONE**
- ✅ Bulk calendar API integration verified working
- ✅ Smart override logic tested with fresh data
- ✅ Human override protection confirmed
- ✅ Force booking functionality implemented
- ✅ All automated tests passing (9 tests, 27 assertions)
- ✅ Production sync performance optimized

## Business Summary

The CoverManager availability sync automatically manages prime/non-prime settings based on real-time restaurant availability:

**How It Works:**
- Checks every 30-minute time slot across all party sizes during operating hours
- Compares CoverManager availability with our platform's default prime/non-prime settings
- Only creates overrides when restaurant availability differs from our defaults

**Smart Override Logic:**
- **Restaurant has availability + Our default is non-prime** → No change needed
- **Restaurant has NO availability + Our default is non-prime** → Override to prime (customer pays upfront)
- **Restaurant has availability + Our default is prime** → Override to non-prime (customer pays at restaurant)  
- **Restaurant has NO availability + Our default is prime** → No change needed

**Human Override Protection:**
The system respects all manual changes made by staff and will never overwrite human decisions.

**Results:**
- Only 14.6% of time slots require overrides (2,707 out of 18,480 possible slots)
- 81% of overrides convert non-prime to prime (when restaurants lack availability)
- Bulk API approach processes all venues efficiently with minimal database impact

---

*This implementation provides efficient automated CoverManager availability synchronization while maintaining operational control and optimal performance.*