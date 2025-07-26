# Customer-Concierge Attribution System

## Overview

The Customer-Concierge Attribution System ensures that returning customers are automatically attributed to the most recent concierge who served them, maintaining proper commission distribution and customer relationship continuity even when customers don't use VIP codes.

**Key Point**: This attribution logic runs during **booking completion** when customer phone numbers are available, not during initial booking creation.

## Business Reasoning

### Why This Feature Exists

1. **Revenue Attribution**: Concierges earn commissions on bookings they facilitate. Without proper attribution, returning customers booking via API without VIP codes would default to fallback concierges, causing revenue loss.

2. **Customer Relationships**: Customers should maintain consistency with their referring concierge to preserve the personal relationship and service quality.

3. **Platform vs API Equity**: Both platform bookings (Reservation Hub/Availability Calendar) and VIP code bookings establish attribution relationships that should persist.

4. **Automatic Attribution**: Customers shouldn't need to remember or re-enter VIP codes for repeat bookings if they've previously been served by a concierge.

## How It Works

### Attribution Priority Logic

The system follows a strict priority order when determining concierge attribution:

```
1. VIP Code (API only)
   ↓ If no VIP code provided
2. Platform Bookings (reservation_hub/availability_calendar)
   ↓ If not platform booking
3. Customer Attribution History (API bookings without VIP code)
   ↓ If no history found
4. Fallback Logic (partner house concierge or authenticated user)
```

### Booking Sources

The system recognizes three distinct booking sources via the `source` field:

- **`api`**: Customer-initiated bookings through the API
  - Can include VIP codes (attributed to VIP code's concierge)
  - Without VIP codes (uses customer attribution history)

- **`reservation_hub`**: Concierge-initiated bookings through the Reservation Hub interface
  - Always attributed to the authenticated concierge making the booking

- **`availability_calendar`**: Concierge-initiated bookings through the Availability Calendar interface
  - Always attributed to the authenticated concierge making the booking

### Customer Identification

Customers are identified and tracked using their phone number in international format (e.g., `+1234567890`). The system:

- Queries historical bookings by exact phone number match
- Orders results by creation date (most recent first)
- Returns the concierge ID from the most recent booking
- Ignores bookings with null concierge_id values

### House VIP Codes

The system supports special "house" VIP codes that are used for tracking and routing but should still allow customer attribution based on history.

#### Configuration
House VIP codes are configured in `config/app.php`:
```php
'house' => [
    'concierge_id' => env('HOUSE_CONCIERGE_ID', 1),
    'vip_codes' => ['HOME', 'DIRECT'],
],
```

#### Behavior
- **Regular VIP codes** (e.g., "ABC123"): Use specific concierge attribution (no customer history check)
- **House VIP codes** (e.g., "HOME", "DIRECT"): Check customer attribution history first, fallback to house concierge
- **VIP code preservation**: The original VIP code is always preserved for tracking purposes

#### Use Cases
- **HOME**: Customer accessed booking through homepage or direct link
- **DIRECT**: Customer accessed booking through direct marketing campaign
- **Future codes**: Additional house codes can be added to config as needed

This allows the system to:
1. **Track the source** of the booking (via VIP code)
2. **Maintain customer relationships** (via historical attribution)
3. **Ensure proper commissions** (via earnings recalculation)

## Technical Implementation

### Booking Flow Overview
1. **Initial Booking Creation**: Customer selects time slot → Basic booking created with current concierge
2. **Booking Completion**: Customer provides details (including phone) → **Apply concierge attribution logic here**
3. **Earnings Recalculation**: If concierge changes → Delete old earnings → Recalculate with new concierge

### Core Actions

#### `GetLastConciergeForCustomer`
**Purpose**: Retrieves the most recent concierge who served a specific customer.

**Location**: `app/Actions/Customer/GetLastConciergeForCustomer.php`

**Usage**:
```php
$conciergeId = GetLastConciergeForCustomer::run('+1234567890');
```

**Logic**:
```sql
SELECT concierge_id FROM bookings 
WHERE guest_phone = '+1234567890' 
  AND concierge_id IS NOT NULL 
ORDER BY created_at DESC 
LIMIT 1;
```

#### `UpdateBookingConciergeAttribution`
**Purpose**: Updates a booking's concierge attribution during completion based on customer history.

**Location**: `app/Actions/Booking/UpdateBookingConciergeAttribution.php`

**Usage**:
```php
$wasUpdated = UpdateBookingConciergeAttribution::run($booking, $customerPhone);
```

**Parameters**:
- `$booking`: Booking model instance to potentially update
- `$customerPhone`: Customer's phone number for attribution lookup

**Features**:
- Only affects API bookings without VIP codes OR with house VIP codes
- Works with all referral types (SMS, QR, app, etc.)
- Automatically recalculates earnings when concierge changes
- Preserves VIP codes for tracking purposes
- Returns boolean indicating if booking was updated

### Integration Points

#### CompleteBooking Action
The `CompleteBooking` action has been enhanced with customer attribution logic:

**Location**: `app/Actions/Booking/CompleteBooking.php` (line ~47)

```php
$formattedPhone = $this->getInternationalFormattedPhoneNumber($formData['phone']);

// Apply customer attribution logic for returning customers
UpdateBookingConciergeAttribution::run($booking, $formattedPhone);

$booking->update([
    'concierge_referral_type' => $formData['r'],
    'guest_phone' => $formattedPhone,
    // ... other fields
]);
```

#### API Booking Completion
The `BookingController` has been updated in two locations for non-prime booking completion:

**Location 1**: `app/Http/Controllers/Api/BookingController.php` (line ~438)
**Location 2**: `app/Http/Controllers/Api/BookingController.php` (line ~724)

```php
app(BookingService::class)->processBooking($booking, $formData);

// Apply customer attribution logic for returning customers
UpdateBookingConciergeAttribution::run($booking, $validatedData['phone']);

$booking->update(['concierge_referral_type' => 'app']);
```

#### Data Flow
```
Booking Completion → UpdateBookingConciergeAttribution → GetLastConciergeForCustomer → 
Concierge Update + Earnings Recalculation
```

## Earnings Recalculation

When concierge attribution changes during booking completion, the system automatically recalculates earnings to ensure proper commission distribution:

### Process
1. **Delete Existing Earnings**: All existing earning records for the booking are removed
2. **Recalculate with New Concierge**: Uses `BookingCalculationService` to recalculate all earnings
3. **Confirm Earnings**: If booking is already confirmed, marks new earnings as confirmed
4. **Update Booking Fields**: Updates `venue_earnings`, `concierge_earnings`, `platform_earnings`, etc.

### Earnings Types Affected
- **Concierge Earnings**: Updated to reflect new concierge's commission rate
- **Venue Earnings**: May change based on new concierge's revenue percentage  
- **Platform Earnings**: Recalculated based on new commission structure
- **Partner/Referral Earnings**: Updated if new concierge has different referral relationships

### Implementation
```php
private function recalculateBookingEarnings(Booking $booking): void
{
    // Delete existing earnings records since concierge has changed
    $booking->earnings()->delete();
    
    // Recalculate earnings with the new concierge
    app(BookingCalculationService::class)->calculateEarnings($booking);
    
    // If booking is confirmed, mark earnings as confirmed
    if (!in_array($booking->status, ['cancelled', 'refunded']) && $booking->confirmed_at) {
        $booking->earnings()->update(['confirmed_at' => $booking->confirmed_at]);
    }
}
```

## Attribution Scenarios

### Scenario 1: VIP Code Booking (API)
```
Customer uses VIP code "ABC123"
→ Source: 'api'
→ VIP Code: Present
→ Result: Use VIP code's concierge (NO ATTRIBUTION CHANGE)
```

### Scenario 2: Platform Booking (Reservation Hub)
```
Concierge creates booking for customer through Reservation Hub
→ Source: 'reservation_hub'
→ Result: Use authenticated concierge (NO ATTRIBUTION CHANGE)
```

### Scenario 3: Returning Customer (API, No VIP Code)
```
Customer previously booked through Concierge A
Customer makes new API booking without VIP code, gets assigned to Concierge B
→ During completion: System detects customer history
→ Result: Update to Concierge A + Recalculate earnings
```

### Scenario 4: New Customer (API, No VIP Code)
```
Customer has never booked before
Customer makes API booking without VIP code
→ During completion: No customer history found
→ Result: Keep original concierge assignment (NO CHANGE)
```

### Scenario 5: House VIP Code with Customer History
```
Customer previously booked through Concierge A
Customer uses house VIP code "HOME" (assigned to house concierge)
→ During completion: System detects customer history
→ Result: Update to Concierge A + Preserve "HOME" VIP code for tracking
```

### Scenario 6: House VIP Code without Customer History
```
New customer uses house VIP code "DIRECT"
→ During completion: No customer history found
→ Result: Keep house concierge assignment (NO CHANGE) + Preserve "DIRECT" VIP code
```

## Testing Strategy

### Test Coverage

The implementation includes comprehensive tests covering:

1. **Priority Logic**: VIP codes take precedence (except house codes)
2. **Platform Bookings**: Use authenticated concierge
3. **Customer History**: Most recent concierge attribution
4. **Phone Number Matching**: Exact phone number lookups
5. **Fallback Behavior**: Default attribution when no history exists
6. **House VIP Codes**: Attribution with tracking preservation
7. **Regular VIP Codes**: No attribution override for specific concierge codes
8. **Edge Cases**: International phone formats, referral type variations

### Test Files
- `tests/Feature/Actions/Booking/UpdateBookingConciergeAttributionTest.php`
- `tests/Feature/Actions/Customer/GetLastConciergeForCustomerTest.php`

### Running Tests
```bash
# Run all attribution tests
./vendor/bin/pest tests/Feature/Actions/Booking/UpdateBookingConciergeAttributionTest.php
./vendor/bin/pest tests/Feature/Actions/Customer/GetLastConciergeForCustomerTest.php

# Run specific test method
./vendor/bin/pest tests/Feature/Actions/Booking/UpdateBookingConciergeAttributionTest.php::it_applies_attribution_to_house_vip_codes

# Test house VIP code functionality specifically
./vendor/bin/pest --filter="house_vip"
```

## Performance Considerations

### Database Impact
- **Single Query**: Each attribution lookup requires one additional database query
- **Indexed Lookups**: Queries use `guest_phone` and `created_at` fields which should be indexed
- **Minimal Overhead**: Query is only executed for API bookings without VIP codes

### Optimization Opportunities
If performance becomes a concern, consider:
1. **Customer Attribution Table**: Dedicated table for faster lookups
2. **Caching**: Cache recent customer-concierge relationships
3. **Background Processing**: Update attribution relationships asynchronously

## Data Privacy & Security

### Phone Number Handling
- Phone numbers are stored in international format for consistency
- Exact string matching prevents cross-contamination between similar numbers
- No additional customer data is stored or tracked beyond existing booking records

### Attribution Persistence
- Attribution is determined at booking creation time
- Historical bookings remain unchanged
- No retroactive attribution changes

## Monitoring & Analytics

### Key Metrics to Track
1. **Attribution Source Distribution**:
   - % of bookings using VIP codes
   - % of bookings using customer history
   - % of bookings using fallback logic

2. **Customer Behavior**:
   - Customers with multiple concierge relationships
   - Average time between customer bookings
   - VIP code usage vs. automatic attribution

3. **Revenue Impact**:
   - Commission distribution accuracy
   - Concierge earnings attribution
   - Customer lifetime value by attribution source

### Database Queries for Analysis
```sql
-- Attribution source distribution
SELECT 
  CASE 
    WHEN vip_code_id IS NOT NULL THEN 'vip_code'
    WHEN source IN ('reservation_hub', 'availability_calendar') THEN 'platform'
    ELSE 'attribution_history'
  END AS attribution_source,
  COUNT(*) as booking_count
FROM bookings 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY attribution_source;

-- Customers with multiple concierge relationships
SELECT 
  guest_phone,
  COUNT(DISTINCT concierge_id) as unique_concierges,
  COUNT(*) as total_bookings
FROM bookings 
WHERE guest_phone IS NOT NULL 
  AND concierge_id IS NOT NULL
GROUP BY guest_phone
HAVING unique_concierges > 1
ORDER BY unique_concierges DESC;
```

## Troubleshooting

### Common Issues

1. **Attribution Not Working**
   - Verify `source` parameter is correctly set
   - Check phone number format consistency
   - Ensure historical bookings have non-null concierge_id

2. **Wrong Concierge Attribution**
   - Confirm VIP code ownership
   - Verify booking source detection
   - Check customer phone number matching

3. **Performance Issues**
   - Monitor database query performance
   - Consider adding indexes on `guest_phone` and `created_at`
   - Review query frequency and optimization opportunities

### Debug Queries
```sql
-- Check customer booking history
SELECT guest_phone, concierge_id, source, created_at
FROM bookings 
WHERE guest_phone = '+1234567890'
ORDER BY created_at DESC;

-- Verify concierge attribution
SELECT b.id, b.guest_phone, b.concierge_id, c.hotel_name, b.source
FROM bookings b
LEFT JOIN concierges c ON b.concierge_id = c.id
WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY b.created_at DESC;
```

## Future Enhancements

### Potential Improvements
1. **Attribution Confidence Scoring**: Weight recent interactions more heavily
2. **Multi-Channel Attribution**: Track attribution across different booking channels
3. **Customer Preference Management**: Allow customers to specify preferred concierges
4. **Attribution Analytics Dashboard**: Real-time visibility into attribution patterns

### Backwards Compatibility
The implementation is fully backwards compatible:
- Existing VIP code functionality unchanged
- Platform booking behavior unchanged  
- Only adds new logic for API bookings without VIP codes
- No database schema changes required