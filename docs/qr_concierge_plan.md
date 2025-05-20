# VIP Access (QR Concierge) Implementation Plan

## 1. Current System Understanding

- Regular concierges: Different earnings based on prime (10-15% tiered) vs non-prime (80%) bookings
- New requirement: VIP Access concierges will receive a 50/50 split for BOTH prime and non-prime bookings
- Super admins need the ability to create multiple QR codes per concierge with individual tracking
- VIP Access signup should be simplified from the standard concierge flow

## 2. Technical Components

### A. Database Changes

- Add `is_vip_access` boolean flag to the `concierges` table
- Add `vip_revenue_percentage` integer field (default 50) to allow for dynamic adjustment
- Create a new `qr_codes` table to track codes with:
  - `id` (primary key)
  - `concierge_id` (foreign key)
  - `concierge_type` (enum: 'regular', 'vip_access')
  - `code` (unique identifier)
  - `location_name` (specific room/villa name)
  - `scans_count` (tracking total scans)
  - `bookings_count` (tracking successful bookings)
  - `revenue_generated` (total revenue from this QR code)
  - `created_at` and other tracking stats fields

- Create a new `qr_stand_requests` table for tracking stand requests:
  - `id` (primary key)
  - `concierge_id` (foreign key)
  - `placement` (location description)
  - `units_requested` (number of stands needed)
  - `needed_by_date` (deadline)
  - `status` (enum: 'pending', 'in_production', 'shipped', 'delivered')
  - `created_at`, `updated_at`, etc.

### B. Constants Update

- Add `VIP_ACCESS_DEFAULT_PERCENTAGE = 50` to `BookingPercentages.php`
- This constant represents the default percentage for new VIP Access concierges
- Individual concierges will store their own custom percentage in the database
- The constant provides a single place to update the default value for future concierges

### C. Earnings Calculation Update

- Modify both `PrimeEarningsCalculationService` and `NonPrimeEarningsCalculationService` to:
  - Check if booking is from a VIP Access concierge
  - Use the concierge's stored `vip_revenue_percentage` value (not the constant)
  - Ensure this applies for both booking types

### D. Admin Interface

- Add section for super admins to:
  - Create new QR codes
  - Assign QR codes to either regular concierges or VIP Access concierges via dropdown selection
  - Name/label each QR code (e.g., "Villa Sunset - Master Bedroom")
  - Manage and view performance of individual QR codes
  - Adjust revenue percentage on a per-concierge basis
  - View and manage QR stand requests

### E. QR Code Tracking

- Enhance QR code generation to:
  - Create unique codes for each location/room
  - Include tracking parameters for specific QR code
  - Track metrics including:
    - Raw number of scans
    - Number of bookings
    - Revenue generated
    - $ earned per scan
    - Average daily earnings
  - Generate printable QR codes with location labels

### F. VIP Access Portal

- Create streamlined registration for VIP Access concierges
- Build a portal for VIP Access concierges to:
  - View their "Active QR Codes" with performance metrics
  - Request new QR stands with form fields:
    - Placement location
    - Number of units needed
    - Date needed by
  - Track status of their stand requests

### G. Notification System

- Email notifications for new stand requests (similar to "Talk to Prima" submissions)
- Future integration with WhatsApp for direct communication
- Internal queue management system for stand production

## 3. Implementation Steps

1. Create database migrations for:
   - Updated fields in `concierges` table
   - New `qr_codes` table with extended metrics
   - New `qr_stand_requests` table
2. Update models and relationships:
   - Modify `Concierge` model to include VIP Access type
   - Create `QrCode` model with relationship to `Concierge`
   - Create `QrStandRequest` model with appropriate relationships
3. Update earnings calculations:
   - Modify calculation services to handle VIP Access percentage
4. Create super admin interface:
   - Build QR code creation interface
   - Create assignment system with concierge type selection
   - Build QR code management screens
   - Create stand request management queue
5. Implement VIP Access portal:
   - Modify existing registration to detect VIP Access type
   - Create QR code performance dashboard
   - Build stand request form
6. Update QR code generation and tracking:
   - Support assignment to different concierge types
   - Implement scan and conversion tracking
   - Calculate performance metrics
7. Create notification system:
   - Email alerts for new stand requests
   - Internal queue management

## 4. User Flow

### Admin Flow

1. Super admin creates new QR code
2. Admin assigns QR code to either regular concierge or VIP Access concierge
3. Admin monitors performance metrics for each QR code
4. Admin manages stand production requests

### VIP Access Concierge Flow

1. Completes simplified registration process
2. Views assigned QR codes and performance metrics
3. Requests new QR stands as needed
4. Receives updates on request status

### Customer Flow

1. Scans QR code at location
2. System tracks the scan event
3. If booking is completed, system attributes to specific QR code
4. VIP Access concierge receives 50/50 split on earnings

## 5. Testing Strategy

- Unit tests for earnings calculations with VIP Access percentage
- Tests for QR code assignment to different concierge types
- Test for QR-specific attribution and metrics tracking
- End-to-end test of stand request system
- Notification system validation

## 6. Future Considerations

- WhatsApp integration for direct communication with VIP Access concierges
- Analytics dashboard specific to QR performance
- Automatic adjustment of revenue percentage based on performance
- Geofencing to ensure QR codes are being used in intended locations
