# BookingsOverview Widget Revenue Calculation Update

## Overview
This document outlines the changes made to the `BookingsOverview` widget (`app/Livewire/BookingsOverview.php`) to better reflect PRIMA's revenue metrics for investor reporting.

## Business Requirements
The goal was to show two key metrics:
1. **Gross Revenue**: All money flowing into PRIMA (before payouts)
2. **PRIMA Share**: Money remaining after venue costs but before concierge costs

## Changes Made

### Widget Metrics (Before vs After)

#### BEFORE:
1. **Bookings** - Total confirmed booking count
2. **PRIME Bookings** - Customer payment amounts (prime bookings only)  
3. **Platform Revenue** - Net platform earnings after all payouts

#### AFTER:
1. **Bookings** - Total confirmed booking count (unchanged)
2. **Gross Revenue** - Total revenue from all sources
3. **PRIMA Share** - Revenue after venue costs, before concierge costs

### SQL Calculation Changes

#### Gross Revenue Calculation
**Before:**
```sql
SUM(total_fee - total_refunded) as total_amount
```
- Only counted prime booking customer payments
- Non-prime bookings contributed $0 (since total_fee = 0)

**After:**
```sql
SUM(CASE 
    WHEN is_prime = true THEN total_fee - total_refunded 
    ELSE ABS(venue_earnings) 
END) as gross_revenue
```
- Prime bookings: Customer payment amounts
- Non-prime bookings: Venue payment amounts (ABS of negative venue_earnings)

#### PRIMA Share Calculation  
**Before:**
```sql
SUM(platform_earnings - platform_earnings_refunded) as platform_revenue
```
- Net platform earnings after all distributions (venues, concierges, partners, referrals)

**After:**
```sql
SUM(CASE 
    WHEN is_prime = true THEN total_fee - total_refunded - venue_earnings 
    WHEN is_prime = false THEN ABS(venue_earnings) 
    ELSE 0 
END) as prima_share
```
- Prime bookings: Customer payments minus venue payouts
- Non-prime bookings: Full venue payment amount

## Revenue Flow Logic

### Prime Bookings (Customer → PRIMA → Distribution)
- **Gross Revenue**: Customer payment (`total_fee`)
- **PRIMA Share**: Customer payment minus venue payout (`total_fee - venue_earnings`)
- **Net Platform**: Remaining after concierge, partner, referral payouts (`platform_earnings`)

### Non-Prime Bookings (Venue → PRIMA → Distribution)  
- **Gross Revenue**: Venue payment (`ABS(venue_earnings)`)
- **PRIMA Share**: Full venue payment (`ABS(venue_earnings)`)
- **Net Platform**: Remaining after concierge bounty, partner, referral payouts (`platform_earnings`)

## Expected Impact

### What Should Change:
1. **Gross Revenue** should be higher than before due to including non-prime venue payments
2. **PRIMA Share** should show revenue before concierge costs (higher than old platform revenue)

### What Might Not Change Much:
- If the date range has few non-prime bookings, Gross Revenue increase might be minimal
- If venue payouts are similar to the sum of (concierge + partner + referral) costs, PRIMA Share might be close to old platform revenue

## Implementation Details

### Files Modified:
1. `app/Livewire/BookingsOverview.php` - Dashboard widget
2. `app/Livewire/Booking/EarningsBreakdown.php` - Individual booking breakdown component
3. `resources/views/livewire/booking/earnings-breakdown.blade.php` - Breakdown view template

### Methods Updated:

#### BookingsOverview Widget:
- `getStats()` - Main statistics calculation
- `getChartData()` - Daily chart data calculation

#### EarningsBreakdown Component:
- `calculateGrossRevenue()` - New method for gross revenue calculation
- `calculatePrimaShare()` - New method for PRIMA share calculation  
- `render()` - Updated to pass new variables to view
- Removed `calculateGrossAmount()` - Replaced with standardized calculations  

### Database Fields Used:
- `total_fee` - Customer payment amount (prime) or 0 (non-prime)
- `venue_earnings` - Venue net earnings (positive for prime, negative for non-prime)
- `platform_earnings` - Platform net earnings after all distributions
- `is_prime` - Boolean flag distinguishing booking types

## Validation

### To verify changes are working:
1. Check that Gross Revenue includes venue payments from non-prime bookings
2. Verify PRIMA Share is higher than old Platform Revenue
3. Confirm the difference represents venue payouts for prime bookings

### Sample Calculation:
For a prime booking with:
- Customer pays: $100
- Venue gets: $60  
- Concierge gets: $10
- Platform net: $30

**Old metrics:**
- PRIME Bookings: $100
- Platform Revenue: $30

**New metrics:**  
- Gross Revenue: $100
- PRIMA Share: $40 ($100 - $60 venue payout)

The $10 difference ($40 - $30) represents concierge and other operational costs.

## Business Value

This change provides clearer visibility into:
1. **Total revenue flowing through PRIMA** (important for growth metrics)
2. **Revenue available before operational costs** (important for margin analysis)
3. **Unified view of both prime and non-prime revenue streams** (complete business picture)

The metrics now better align with standard financial reporting where gross revenue and operational margins are key indicators for investors.

## Additional Changes: Individual Booking Breakdown

### EarningsBreakdown Component Updates

The individual booking earnings breakdown (shown on booking detail pages) has also been updated to display consistent metrics:

#### Before:
- Individual earnings by party
- PRIMA Revenue (inconsistent calculation)
- Total Amount

#### After:  
- Individual earnings by party
- **Gross Revenue** (total money received)
- **PRIMA Share** (revenue after venue costs)
- Total Amount

#### Calculation Logic:
**Gross Revenue:**
- Prime: `total_fee` (customer payment)
- Non-Prime: `ABS(venue_earnings)` (venue payment)

**PRIMA Share:**
- Prime: `total_fee - venue_earnings` (customer payment minus venue payout)
- Non-Prime: `ABS(venue_earnings)` (full venue payment)

This ensures consistency between the dashboard metrics and individual booking breakdowns, providing a unified view of revenue reporting across the application.