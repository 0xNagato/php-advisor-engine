# PRIMA Booking Calculation System – Technical Documentation

## Overview

The PRIMA platform facilitates both prime and non-prime bookings between venues and customers, mediated by concierges. This document outlines the technical aspects of how bookings are calculated and how earnings are distributed among the various parties involved.

## Key Stakeholders

1. **PRIMA Platform**: The central system that manages bookings and calculations.
2. **Venues**: Establishments that receive bookings.
3. **Concierges**: Intermediaries who facilitate bookings for customers.
4. **Partners**: Entities that refer venues and concierges to the platform, earning on both prime and non-prime bookings.

## Earning Types

The system uses an `EarningType` enum to categorize different types of earnings:

```php
enum EarningType: string implements HasLabel
{
    case VENUE = 'venue';
    case PARTNER_VENUE = 'partner_venue';
    case CONCIERGE = 'concierge';
    case PARTNER_CONCIERGE = 'partner_concierge';
    case CONCIERGE_REFERRAL_1 = 'concierge_referral_1';
    case CONCIERGE_REFERRAL_2 = 'concierge_referral_2';
    case VENUE_PAID = 'venue_paid';
    case CONCIERGE_BOUNTY = 'concierge_bounty';
    case REFUND = 'refund';
}
```

## Customizable Booking Percentages

The system uses a set of customizable percentages defined in the `BookingPercentages` class. These percentages can be adjusted to fine-tune the earnings distribution across different booking types and stakeholders.

```php
namespace App\Constants;

class BookingPercentages
{
    // Platform percentages
    public const int PLATFORM_PERCENTAGE_CONCIERGE = 20;
    public const int PLATFORM_PERCENTAGE_VENUE = 10;

    // Non-prime booking percentages
    public const int NON_PRIME_CONCIERGE_PERCENTAGE = 80;
    public const int NON_PRIME_PROCESSING_FEE_PERCENTAGE = 10;
    public const int NON_PRIME_VENUE_PERCENTAGE = -110; // Represents outgoing payment

    // Prime booking referral percentages
    public const int PRIME_REFERRAL_LEVEL_1_PERCENTAGE = 10;
    public const int PRIME_REFERRAL_LEVEL_2_PERCENTAGE = 5;
    
    // Maximum partner earnings percentage
    public const int MAX_PARTNER_EARNINGS_PERCENTAGE = 20;
}
```

## Prime Booking Calculation Scenarios

### Scenario 1: Standard Prime Booking

In this scenario, both the concierge and the venue are referred by partners.

Example Calculation:

- Total Booking Fee: $200.00
- Venue Earnings: $120.00 (60%)
- Concierge Earnings: $20.00 (10%)
- Partner Earnings (Venue): $6.00 (10% of remainder)
- Partner Earnings (Concierge): $6.00 (10% of remainder)
- Platform Earnings: $48.00 (remainder)

### Scenario 2: Prime Booking with Level 1 Concierge Referral

In this scenario, the booking concierge was referred by another concierge (level 1), and both concierge and venue have partner referrals.

Example Calculation:

- Total Booking Fee: $200.00
- Venue Earnings: $120.00 (60%)
- Booking Concierge Earnings: $20.00 (10%)
- Level 1 Referring Concierge Earnings: $6.00 (10% of remainder)
- Partner Earnings (Venue): $5.40 (10% of remaining balance)
- Partner Earnings (Concierge): $5.40 (10% of remaining balance)
- Platform Earnings: $43.20 (final remainder)

### Scenario 3: Prime Booking with Level 1 and Level 2 Concierge Referrals

In this scenario, the booking concierge was referred by another concierge (level 1), who in turn was referred by a third concierge (level 2). Both the concierge and venue also have partner referrals.

Example Calculation:

- Total Booking Fee: $200.00
- Venue Earnings: $120.00 (60%)
- Booking Concierge Earnings: $20.00 (10%)
- Level 1 Referring Concierge Earnings: $6.00 (10% of remainder)
- Level 2 Referring Concierge Earnings: $3.00 (5% of remainder)
- Partner Earnings (Venue): $5.10 (10% of remaining balance)
- Partner Earnings (Concierge): $5.10 (10% of remaining balance)
- Platform Earnings: $40.80 (final remainder)

## Non-Prime Booking Calculation Scenarios

Non-prime bookings have a different structure but now also include partner and concierge referral earnings.

### Key Characteristics of Non-Prime Bookings:

- The venue sets a fixed fee per head (e.g., $10 per guest).
- The minimum guest count is 2.
- The venue pays the platform for these bookings.
- The concierge receives a bounty.
- The booking fee for the customer is $0.
- The platform charges a processing fee on top of the bounty.
- Partners and referring concierges can now earn from non-prime bookings.

### Standard Non-Prime Booking Calculation

Example Calculation for a booking with 2 guests and a $10 per head fee:

- Customer Booking Fee: $0
- Venue Fee: $20.00 (2 guests * $10 per head)
- Concierge Bounty: $16.00 (80% of the venue fee)
- Platform Earnings: $6.00 (30% of the venue fee, including processing fee)
- Venue Payment: -$22.00 (Venue fee + Processing fee)

### Non-Prime Booking with Partner Referrals

Example Calculation for a booking with 2 guests, a $10 per head fee, and a partner with 10% referral percentage:

- Customer Booking Fee: $0
- Venue Fee: $20.00 (2 guests * $10 per head)
- Concierge Bounty: $16.00 (80% of the venue fee)
- Platform Earnings before Partner: $6.00 (30% of the venue fee)
- Partner Earnings: $0.60 (10% of platform earnings)
- Platform Earnings after Partner: $5.40
- Venue Payment: -$22.00 (Venue fee + Processing fee)

### Non-Prime Booking with Concierge Referrals

Example Calculation for a booking with 2 guests, a $10 per head fee, and a referring concierge:

- Customer Booking Fee: $0
- Venue Fee: $20.00 (2 guests * $10 per head)
- Concierge Bounty: $16.00 (80% of the venue fee)
- Platform Earnings before Referral: $6.00 (30% of the venue fee)
- Level 1 Referring Concierge Earnings: $0.60 (10% of platform earnings)
- Platform Earnings after Referral: $5.40
- Venue Payment: -$22.00 (Venue fee + Processing fee)

### Non-Prime Booking with Partner and Concierge Referrals

Example Calculation for a booking with 2 guests, a $10 per head fee, a partner with 10% referral percentage, and a referring concierge:

- Customer Booking Fee: $0
- Venue Fee: $20.00 (2 guests * $10 per head)
- Concierge Bounty: $16.00 (80% of the venue fee)
- Platform Earnings before Partner/Referral: $6.00 (30% of the venue fee)
- Partner Earnings: $0.60 (10% of platform earnings)
- Level 1 Referring Concierge Earnings: $0.60 (10% of platform earnings)
- Platform Earnings after Partner/Referral: $4.80
- Venue Payment: -$22.00 (Venue fee + Processing fee)

### Non-Prime Booking with Different Guest Count

Example Calculation for a booking with 5 guests and a $10 per head fee:

- Customer Booking Fee: $0
- Venue Fee: $50.00 (5 guests * $10 per head)
- Concierge Bounty: $40.00 (80% of the venue fee)
- Platform Earnings: $15.00 (30% of the venue fee, including processing fee)
- Venue Payment: -$55.00 (Venue fee + Processing fee)

### Non-Prime Booking with Custom Fee

Example Calculation for a booking with 2 guests and a $15 per head fee:

- Customer Booking Fee: $0
- Venue Fee: $30.00 (2 guests * $15 per head)
- Concierge Bounty: $24.00 (80% of the venue fee)
- Platform Earnings: $9.00 (30% of the venue fee, including processing fee)
- Venue Payment: -$33.00 (Venue fee + Processing fee)

## Partner Earnings Cap

For both prime and non-prime bookings, partner earnings are capped at 20% of the remainder after paying out the venue and concierge. This cap is defined in the `BookingPercentages` class as `MAX_PARTNER_EARNINGS_PERCENTAGE`.

If a partner refers both the venue and the concierge for the same booking, the total earnings are still capped at 20% of the remainder, with the earnings split proportionally between the venue and concierge referrals.
