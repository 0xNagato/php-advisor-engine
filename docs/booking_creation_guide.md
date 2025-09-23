# PRIMA Booking Creation Guide

## Overview

This document explains how to properly create bookings in the PRIMA system. The booking system is complex and involves multiple steps, validations, and business logic.

## Booking Flow Architecture

### 1. Schedule Templates (Foundation)

**ScheduleTemplate Model:**
- Defines venue operating hours and availability
- Contains: venue_id, day_of_week, start_time, end_time, prime_time, prime_time_fee
- Example: Monday 18:00-22:00 (prime time), Monday 22:00-02:00 (non-prime)

**ScheduleWithBookingMV (Materialized View):**
- Real-time view of schedule availability with booking counts
- Shows remaining tables for each time slot
- Updates automatically as bookings are made/cancelled

### 2. Booking Creation Process

#### Step 1: Get Available Schedules
```php
// Use ReservationService to get available venues and timeslots
$reservation = new ReservationService(
    date: $date,
    guestCount: $guestCount,
    reservationTime: $time,
    timeslotCount: 5,
    region: $region,
    vipContext: $vipContext
);

// Get venues with their available schedules
$availableVenues = $reservation->getAvailableVenues();
$timeslots = $reservation->getTimeslotHeaders();
```

#### Step 2: Create Booking (PENDING Status)
```php
// Use CreateBooking action
$booking = CreateBooking::run(
    scheduleTemplateId: $scheduleTemplateId,
    data: [
        'date' => $date,
        'guest_count' => $guestCount,
    ],
    vipCode: $vipCode, // Optional
    source: 'api',
    device: 'web',
    vipSessionId: $sessionId // Optional
);

// Returns CreateBookingReturnData with booking details
// Booking status is PENDING at this point
```

#### Step 3: Complete Booking (CONFIRMED Status)
```php
// Use CompleteBooking action for final confirmation
$result = CompleteBooking::run(
    $booking,
    $paymentIntentId, // Required for prime bookings
    [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'phone' => $phone,
        'email' => $email,
        'notes' => $notes,
        'r' => $referralCode,
    ]
);

// Booking status becomes CONFIRMED (unless on risk hold)
```

## Key Components

### Booking Model Fields

**Core Fields:**
- `schedule_template_id` (foreign key to ScheduleTemplate)
- `concierge_id` (foreign key to Concierge)
- `guest_count` (number of guests)
- `booking_at` (Carbon datetime object)
- `status` (BookingStatus enum)
- `vip_code_id` (foreign key to VipCode, optional)

**Business Logic Fields:**
- `is_prime` (boolean - determines pricing)
- `total_fee` (integer - fee in cents)
- `venue_fee` (integer - venue's portion)
- `concierge_fee` (integer - concierge's portion)
- `platform_fee` (integer - platform's portion)
- `currency` (string - USD, EUR, etc.)

**Guest Information:**
- `guest_first_name`
- `guest_last_name`
- `guest_email`
- `guest_phone`

**Payment & Tracking:**
- `stripe_payment_intent_id` (for prime bookings)
- `stripe_charge_id` (for prime bookings)
- `confirmed_at` (when booking was confirmed)
- `source` (web, api, mobile_app)
- `device` (web, mobile_app)

### Booking Status Flow

```php
enum BookingStatus: string
{
    case PENDING = 'pending';           // Initial state after creation
    case GUEST_ON_PAGE = 'guest_on_page'; // Guest viewing booking form
    case REVIEW_PENDING = 'review_pending'; // On risk hold
    case CONFIRMED = 'confirmed';       // Successfully confirmed
    case VENUE_CONFIRMED = 'venue_confirmed'; // Confirmed by venue
    case COMPLETED = 'completed';       // Booking completed
    case CANCELLED = 'cancelled';       // Cancelled
    case ABANDONED = 'abandoned';       // Abandoned by guest
    case REFUNDED = 'refunded';         // Refunded
    case NO_SHOW = 'no_show';           // No show
    case PARTIALLY_REFUNDED = 'partially_refunded'; // Partial refund

    // Reporting statuses (used in VIP code booking counts)
    public const array REPORTING_STATUSES = [
        self::CONFIRMED,
        self::VENUE_CONFIRMED,
        self::COMPLETED,
        self::REFUNDED,
        self::PARTIALLY_REFUNDED,
    ];
}
```

## Business Rules & Validations

### 1. Date & Time Validations
- Bookings cannot be made in the past
- Same-day bookings must be at least 35 minutes in advance
- Maximum advance booking: 30 days (configurable)
- Must respect venue's cutoff times

### 2. Availability Checks
- Venue must be active (status: active or hidden)
- Schedule template must be available
- Must have remaining table capacity
- Must not conflict with existing bookings

### 3. VIP Code Attribution
- VIP codes are optional
- When provided, booking is attributed to VIP code
- Only confirmed bookings count toward VIP code earnings
- VIP codes must be active to be attributed

### 4. Concierge Assignment
- Every booking must have a concierge_id
- VIP bookings are assigned to the VIP code's concierge
- Regular bookings are assigned to the authenticated concierge

### 5. Risk Assessment
- All bookings go through risk scoring
- High-risk bookings are placed on hold
- Risk score affects booking status and notifications

## API Endpoints

### GET /api/calendar
**Purpose:** Get available venues and timeslots
**Authentication:** VIP session token required
**Returns:** Venues with schedule information and availability

### POST /api/bookings
**Purpose:** Create a new booking (PENDING status)
**Returns:** Booking details with payment intent (for prime bookings)

### POST /api/bookings/{id}/complete
**Purpose:** Complete/confirm a booking
**Returns:** Confirmed booking with invoice information

### GET /api/bookings/{id}
**Purpose:** Get booking details
**Authorization:** Booking must belong to authenticated concierge

## Required Data for Booking Creation

### Schedule Template ID
Must be obtained from the calendar API first. Each schedule template represents:
- A specific venue
- A specific day of the week
- A specific time slot (e.g., 18:00-22:00)
- Whether it's prime time or not

### Valid Venue
- Must have active status
- Must have available tables
- Must be operating during the requested time

### Guest Information
- First name, last name
- Phone number (required for non-prime bookings)
- Email (optional)
- Notes (optional)

### Payment Information (Prime Bookings)
- Stripe payment intent ID
- Must be processed client-side before completing booking

## Common Pitfalls

### 1. Using Wrong Schedule Template ID
**Problem:** Creating bookings with schedule_template_id that doesn't exist or isn't available
**Solution:** Always get schedule_template_id from the calendar API first

### 2. Creating Bookings Without Proper Status Flow
**Problem:** Creating bookings directly with CONFIRMED status bypassing validation
**Solution:** Follow the proper flow: Create → Complete

### 3. Missing Required Guest Information
**Problem:** Non-prime bookings require phone number for conflict detection
**Solution:** Always provide guest phone for non-prime bookings

### 4. Ignoring Venue Availability
**Problem:** Creating bookings for venues that aren't actually available
**Solution:** Use the calendar API to check real-time availability

### 5. Not Handling VIP Code Attribution
**Problem:** Bookings not properly attributed to VIP codes
**Solution:** Include vip_code parameter when creating booking

## Example Implementation

```php
// 1. Get availability
$reservation = new ReservationService(
    date: '2025-01-15',
    guestCount: 4,
    reservationTime: '19:00:00'
);

$availableVenues = $reservation->getAvailableVenues();

// 2. Find suitable schedule
foreach ($availableVenues as $venue) {
    foreach ($venue->schedules as $schedule) {
        if ($schedule->is_bookable && $schedule->remaining_tables >= 4) {
            $scheduleTemplateId = $schedule->schedule_template_id;
            break 2;
        }
    }
}

// 3. Create booking
$booking = CreateBooking::run(
    scheduleTemplateId: $scheduleTemplateId,
    data: [
        'date' => '2025-01-15',
        'guest_count' => 4,
    ],
    vipCode: VipCode::findByCode('VIP123'),
    source: 'api'
);

// 4. Complete booking (for non-prime)
CompleteBooking::run(
    $booking,
    null, // No payment intent for non-prime
    [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '+1234567890',
        'email' => 'john@example.com'
    ]
);
```

## Testing Considerations

### VIP Code Manager Display
- Only bookings with status in `REPORTING_STATUSES` are counted
- Date filtering applies (Past 30 Days, etc.)
- Only confirmed bookings show up in VIP code earnings

### Booking Status Requirements
- Bookings must be CONFIRMED to count toward VIP code earnings
- Bookings must be linked to valid VIP codes
- Bookings must have proper concierge attribution

### Proper Test Data Creation
1. Use real venues from the database
2. Use real schedule templates
3. Follow the proper booking flow (Create → Complete)
4. Ensure bookings have CONFIRMED status
5. Verify VIP code relationships are correct

This ensures that test bookings show up properly in the VIP code manager and reflect the actual booking system behavior.
