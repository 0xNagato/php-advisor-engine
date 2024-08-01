# PRIMA Booking Calculation System – Technical Documentation

## Overview

The PRIMA platform facilitates both prime and non-prime bookings between restaurants and customers, mediated by concierges. This document outlines the technical aspects of how bookings are calculated and how earnings are distributed among the various parties involved.

## Key Stakeholders

1. **PRIMA Platform**: The central system that manages bookings and calculations.
2. **Restaurants**: Establishments that receive bookings.
3. **Concierges**: Intermediaries who facilitate bookings for customers.
4. **Partners**: Entities that can refer restaurants or concierges to the platform.

## Earning Types

The system uses an `EarningType` enum to categorize different types of earnings:

```php
enum EarningType: string implements HasLabel
{
    case RESTAURANT = 'restaurant';
    case PARTNER = 'partner_restaurant';
    case CONCIERGE = 'concierge';
    case CONCIERGE_REFERRAL_1 = 'concierge_referral_1';
    case CONCIERGE_REFERRAL_2 = 'concierge_referral_2';
    case RESTAURANT_PAID = 'restaurant_paid';
    case CONCIERGE_BOUNTY = 'concierge_bounty';
}
```

## Prime Booking Calculation Scenarios

### Scenario 1: Partner Referred Both Concierge and Restaurant

In this scenario, a single partner has referred both the concierge and the restaurant involved in the booking.

Example Calculation:

- Total Booking Fee: $200.00
- Restaurant Earnings: $120.00 (60%)
- Concierge Earnings: $20.00 (10%)
- Partner Earnings (Restaurant): $3.60 (6% of remainder)
- Partner Earnings (Concierge): $3.60 (6% of remainder)
- Platform Earnings: $52.80 (remainder)

### Scenario 2: Different Partners Referred Concierge and Restaurant

In this scenario, one partner referred the concierge, and another partner referred the restaurant.

Example Calculation:

- Total Booking Fee: $200.00
- Restaurant Earnings: $120.00 (60%)
- Concierge Earnings: $20.00 (10%)
- Partner 1 Earnings (Restaurant): $3.60 (6% of remainder)
- Partner 2 Earnings (Concierge): $3.60 (6% of remainder)
- Platform Earnings: $52.80 (remainder)

### Scenario 3: Concierge with Level 1 Referral

In this scenario, the booking concierge was referred by another concierge (level 1).

Example Calculation:

- Total Booking Fee: $200.00
- Restaurant Earnings: $120.00 (60%)
- Booking Concierge Earnings: $20.00 (10%)
- Level 1 Referring Concierge Earnings: $6.00 (10% of remainder)
- Platform Earnings: $54.00 (remainder)

### Scenario 4: Concierge with Level 1 and Level 2 Referrals

In this scenario, the booking concierge was referred by another concierge (level 1), who in turn was referred by a third concierge (level 2).

Example Calculation:

- Total Booking Fee: $200.00
- Restaurant Earnings: $120.00 (60%)
- Booking Concierge Earnings: $20.00 (10%)
- Level 1 Referring Concierge Earnings: $6.00 (10% of remainder)
- Level 2 Referring Concierge Earnings: $3.00 (5% of remainder)
- Platform Earnings: $51.00 (remainder)

## Non-Prime Booking Calculation Scenarios

Non-prime bookings have a different structure and calculation method compared to prime bookings.

### Key Characteristics of Non-Prime Bookings:

- The restaurant sets a fixed fee per head (e.g., $10 per guest).
- The minimum guest count is 2.
- The restaurant pays the platform for these bookings.
- The concierge receives a bounty.
- The booking fee for the customer is $0.
- The platform charges a 7% processing fee on top of the bounty.

### Non-Prime Booking Calculation

Example Calculation for a booking with 2 guests and a $10 per head fee:

- Customer Booking Fee: $0
- Restaurant Fee: $20.00 (2 guests * $10 per head)
- Concierge Bounty: $18.00 (90% of the restaurant fee)
- Platform Earnings from Concierge: $2.00 (10% of the restaurant fee)
- Platform Processing Fee: $1.40 (7% of the restaurant fee)
- Total Platform Earnings: $3.40 (17% of the restaurant fee)
- Restaurant Payment: -$22.00 (Restaurant fee + Processing fee)

Breakdown:

- Concierge Earnings: $18.00 (90% of $20.00)
- Platform Earnings: $3.40 (17% of $20.00)
- Restaurant Payment: -$22.00 ($20.00 + $2.00)

### Non-Prime Booking with Different Guest Count

Example Calculation for a booking with 5 guests and a $10 per head fee:

- Customer Booking Fee: $0
- Restaurant Fee: $50.00 (5 guests * $10 per head)
- Concierge Bounty: $45.00 (90% of the restaurant fee)
- Platform Earnings from Concierge: $5.00 (10% of the restaurant fee)
- Platform Processing Fee: $3.50 (7% of the restaurant fee)
- Total Platform Earnings: $8.50 (17% of the restaurant fee)
- Restaurant Payment: -$55.00 (Restaurant fee + Processing fee)

### Non-Prime Booking with Custom Fee

Example Calculation for a booking with 2 guests and a $15 per head fee:

- Customer Booking Fee: $0
- Restaurant Fee: $30.00 (2 guests * $15 per head)
- Concierge Bounty: $27.00 (90% of the restaurant fee)
- Platform Earnings from Concierge: $3.00 (10% of the restaurant fee)
- Platform Processing Fee: $2.10 (7% of the restaurant fee)
- Total Platform Earnings: $5.10 (17% of the restaurant fee)
- Restaurant Payment: -$33.00 (Restaurant fee + Processing fee)

## Calculation Process for Non-Prime Bookings

1. **Booking Initiation**:
    - A non-prime booking is created with details such as restaurant, concierge, guest count, etc.

2. **Restaurant Fee Calculation**:
    - Calculate the total restaurant fee based on the restaurant's per-head fee and guest count.

3. **Concierge Bounty Calculation**:
    - Calculate 90% of the restaurant fee as the concierge bounty.

4. **Platform Earnings Calculation**:
    - Calculate 10% of the restaurant fee as platform earnings from the concierge portion.
    - Calculate an additional 7% of the restaurant fee as the processing fee.

5. **Restaurant Payment Calculation**:
    - Calculate the amount the restaurant needs to pay by adding the restaurant fee and the processing fee (107% of the restaurant fee).

6. **Recording**:
    - Store individual earning records for the concierge (type: CONCIERGE_BOUNTY) and the restaurant (type: RESTAURANT_PAID).
    - Update the booking record with the calculated earnings and payments.

## Key Considerations

- Implement proper error handling and logging for debugging purposes.
- Regularly audit the calculation system to ensure accuracy and fairness.
- Ensure that the calculation process can handle both prime and non-prime bookings correctly.
- Consider adding functionality to easily switch a booking between prime and non-prime status if needed.
- Implement validation to ensure the minimum guest count (2) is met for non-prime bookings.
- Clearly communicate to customers that non-prime bookings are free for them, but explain the value proposition for the restaurant.
- Consider implementing a system to handle edge cases or special promotions that may affect the standard calculation process.
- Regularly review and update the calculation percentages to ensure they align with business goals and market conditions.
