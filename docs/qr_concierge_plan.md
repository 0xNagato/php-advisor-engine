# QR Concierge Implementation Plan

## Executive Summary

The VIP Access program introduces a new revenue channel by leveraging prime real estate locations (like luxury villas, high-end hotels, and vacation rentals) to place branded QR codes that drive bookings. Unlike traditional concierges who work directly at venues, VIP Access concierges are property owners or managers who place PRIMA QR codes in strategic locations within their properties, allowing guests to easily book restaurant reservations.

### Business Value

- **New Revenue Channel**: Expands PRIMA's reach beyond traditional hotel concierges into luxury vacation rentals and private villas
- **50/50 Revenue Split**: Creates an attractive, simplified commission structure for property owners/managers (50% for all bookings regardless of prime status)
- **Scalable Distribution**: Each VIP Access concierge can have multiple QR codes placed across different properties or locations
- **Performance Tracking**: Detailed analytics allow tracking of performance by individual QR code location
- **Quality Control**: Stand request system ensures professional presentation of PRIMA branding

### Key Differentiators from Regular Concierges

- Simplified 50/50 revenue split (vs. tiered 10-15% for prime and 80% for non-prime)
- Multiple QR codes per concierge with individual performance tracking
- Custom QR stands with PRIMA branding for professional presentation
- Designed for property owners/managers rather than hotel staff

## 1. Current System Understanding

- Regular concierges: Different earnings based on prime (10-15% tiered) vs non-prime (80%) bookings
- New requirement: VIP Access concierges will receive a 50/50 split for BOTH prime and non-prime bookings
- Super admins need the ability to create multiple QR codes per concierge with individual tracking
- VIP Access signup should be simplified from the standard concierge flow

## 2. Current Implementation (Existing Code)

### A. Bulk QR Code System

The system already has a robust QR code generation and management system with:

- `QrCode` model for tracking QR codes with:
  - URL keys and short URL integration
  - Assignment to concierges
  - Scan tracking (count and last scanned date)
  - Active/inactive status

- QR Code Generation Actions:
  - `GenerateQrCodes`: Creates batches of QR codes with unique keys
  - `GenerateQrCodeWithLogo`: Creates branded QR codes with PRIMA logo
  - `AssignQrCodeToConcierge`: Assigns QR codes to specific concierges

- Admin Interface in Filament:
  - `QrCodeResource`: Full management UI for QR codes
  - Batch generation of QR codes
  - Assignment to concierges
  - Visit statistics tracking

### B. Current VIP Code System

- `VipCode` model that associates codes with concierges
- Concierge-specific QR code generation
- Basic tracking of bookings

## 3. Required Changes and Additions

### A. Database Changes

- Add to `concierges` table:
  - `is_qr_concierge` boolean flag
  - `revenue_percentage` integer field (default 50)

- Create a new `qr_stand_requests` table for tracking stand requests:
  - `id` (primary key)
  - `concierge_id` (foreign key)
  - `placement` (location description)
  - `units_requested` (number of stands needed)
  - `needed_by_date` (deadline)
  - `status` (enum: 'pending', 'in_production', 'shipped', 'delivered')
  - `created_at`, `updated_at`, etc.

- Add to existing `bulk_qr_codes` table:
  - `location_name` for tracking placement
  - `revenue_generated` for tracking revenue
  - `bookings_count` for tracking successful bookings

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

### D. Admin Interface Enhancements

- Extend `ConciergeResource` to include VIP Access options:
  - Toggle for VIP Access designation
  - Field for custom revenue percentage
  - This allows converting existing concierges to VIP Access

- Update `QrCodeResource` to:
  - Add location tracking fields
  - Add performance metrics display
  - Filter by VIP Access concierges
  - Modify the "Assign QR Code" functionality to include:
    - Two separate dropdown lists (Regular Concierges and VIP Access)
    - Option to invite someone to become a VIP Access concierge after assigning a QR code
    - Clear labeling to distinguish between regular and VIP Access concierges

- Create a new `QrStandRequestResource` for managing stand requests:
  - Production queue management
  - Status tracking and updates
  - Internal admin notes

- Add VIP Access toggle to concierge invitation forms:
  - Update `ConciergeInvitationForms` component used by partners and concierges
  - Add option for super admins to designate invited concierges as VIP Access
  - Set default revenue percentage when inviting as VIP Access

### E. QR Code Tracking Enhancement

- Leverage existing scan tracking from `QrCode`
- Add additional metrics tracking for:
  - Raw number of scans
  - Number of bookings
  - Revenue generated
  - $ earned per scan
  - Average daily earnings

### F. VIP Access Dashboard Features

- Enhance existing concierge dashboard with special features for VIP Access concierges:
  - Add "Active QR Codes" area showing all assigned QR codes with performance metrics:
    - Raw number of scans
    - Number of bookings
    - Revenue generated
    - $ earned per scan
    - Average daily earnings
  - Stand request form with:
    - Placement location
    - Number of units needed
    - Date needed by
  - Status tracking for stand requests

### G. Notification System

- Email notifications for new stand requests:
  - Format similar to "Talk to PRIMA" submissions
  - Include complete VIP Access concierge information
  - Allow direct reply to communicate with the concierge
- Future WhatsApp integration for direct communication
- Internal queue management for stand production

## 4. Implementation Steps

1. Create database migrations for:
   - New fields in `concierges` table
   - New fields in `bulk_qr_codes` table
   - New `qr_stand_requests` table

2. Update models and relationships:
   - Modify `Concierge` model to include VIP Access fields
   - Extend `QrCode` model with additional metrics fields
   - Create `QrStandRequest` model with appropriate relationships

3. Update earnings calculations:
   - Modify calculation services to handle VIP Access percentage

4. Enhance admin interfaces:
   - Update `ConciergeResource` with VIP Access fields
   - Extend `QrCodeResource` with location and metrics
   - Create new resource for stand request management

5. Implement VIP Access portal:
   - Create QR code performance dashboard
   - Build stand request form
   - Implement status tracking

6. Update QR code generation and tracking:
   - Leverage existing generation actions with additional tracking
   - Implement revenue and conversion tracking

7. Create notification system:
   - Email alerts for stand requests
   - Internal queue management

## 5. User Flow

### Admin Flow

1. Super admin creates bulk QR codes using existing functionality
2. Admin assigns QR code to either:
   - An existing concierge (from Regular or VIP Access dropdown)
   - A new person (initiating the VIP Access invitation process)
3. Admin monitors performance metrics for each QR code
4. Admin manages stand production requests through the queue system

### VIP Access Concierge Flow

1. Receives invitation and completes simplified registration process
2. Views their "Active QR Codes" with performance metrics
3. Requests new QR stands as needed
4. Receives updates on request status

### Customer Flow

1. Scans QR code at location
2. System tracks the scan event
3. If booking is completed, system attributes to specific QR code
4. VIP Access concierge receives 50/50 split on earnings

## 6. Testing Strategy

- Unit tests for earnings calculations with VIP Access percentage
- Tests for QR code assignment to VIP Access concierges
- Test for QR-specific attribution and metrics tracking
- End-to-end test of stand request system
- Notification system validation

## 7. Future Considerations

- WhatsApp integration for direct communication with VIP Access concierges
- Analytics dashboard specific to QR performance
- Automatic adjustment of revenue percentage based on performance
- Geofencing to ensure QR codes are being used in intended locations
