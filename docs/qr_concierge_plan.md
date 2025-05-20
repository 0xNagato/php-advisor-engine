# QR Concierge Implementation Plan

## 1. Current System Understanding

- Regular concierges: Different earnings based on prime (10-15% tiered) vs non-prime (80%) bookings
- New requirement: QR concierges will receive a 50/50 split for BOTH prime and non-prime bookings
- Super admins need the ability to create multiple QR codes per concierge with individual tracking
- QR concierge signup should be simplified from the standard concierge flow

## 2. Technical Components

### A. Database Changes

- Add `is_qr_concierge` boolean flag to the `concierges` table
- Add `qr_revenue_percentage` integer field (default 50) to allow for dynamic adjustment
- Add `qr_location` string field to track placement location
- Create a new `qr_codes` table to track multiple codes per concierge with:
  - `id` (primary key)
  - `concierge_id` (foreign key)
  - `code` (unique identifier)
  - `location_name` (specific room/villa name)
  - `created_at` and tracking stats fields

### B. Constants Update

- Add `QR_CONCIERGE_PERCENTAGE = 50` to `BookingPercentages.php`
- Ensure this applies to both prime and non-prime calculations

### C. Earnings Calculation Update

- Modify both `PrimeEarningsCalculationService` and `NonPrimeEarningsCalculationService` to:
  - Check if booking is from a QR concierge
  - Override standard percentages with the concierge's `qr_revenue_percentage`
  - Ensure this applies for both booking types

### D. Admin Interface

- Add section for super admins to:
  - Invite QR concierges
  - Specify number of QR codes needed
  - Name/label each QR code (e.g., "Villa Sunset - Master Bedroom")
  - Manage and view performance of individual QR codes
  - Adjust revenue percentage if needed

### E. QR Code Tracking

- Enhance QR code generation to:
  - Create unique codes for each location/room
  - Include tracking parameters for specific QR code (not just concierge)
  - Generate printable QR codes with location labels

### F. Simplified Signup Flow

- Create streamlined registration for QR concierges:
  - Remove hotel name field
  - Simplify agreement terms focusing on villa placement
  - Emphasize the 50/50 revenue share model
  - Pre-fill any information provided during invitation

## 3. Implementation Steps

1. Create database migrations for:
   - New fields in `concierges` table
   - New `qr_codes` table
2. Update models and relationships:
   - Modify `Concierge` model
   - Create `QrCode` model with relationship to `Concierge`
3. Update earnings calculations:
   - Modify both calculation services to handle QR concierge percentage
4. Create super admin interface:
   - Add QR concierge invitation functionality
   - Build multi-code generation interface
   - Create QR code management screens
5. Implement simplified signup flow:
   - Modify existing registration to detect QR concierge type
   - Create streamlined form version
6. Update QR code generation:
   - Support multiple codes per concierge
   - Add location tracking to each code
7. Create reporting system:
   - Track performance by concierge
   - Track performance by individual QR code

## 4. User Flow

1. Super admin invites QR concierge
2. Super admin specifies number of QR codes and their locations
3. Invited person completes simplified registration
4. System generates unique QR codes for each specified location
5. QR codes are placed in villas/rooms
6. Guests scan codes and make bookings
7. System tracks which specific QR code was used
8. Earnings are calculated with 50/50 split (or custom percentage)
9. Reports show performance by concierge and individual QR code

## 5. Testing Strategy

- Unit tests for earnings calculations with QR concierge percentage
- Tests for multi-code generation system
- Test for QR-specific attribution in booking flow
- End-to-end test of simplified registration
- Performance tracking validation for individual QR codes

## 6. Future Considerations

- Analytics dashboard specific to QR performance
- Automatic adjustment of revenue percentage based on performance
- Geofencing to ensure QR codes are being used in intended locations
