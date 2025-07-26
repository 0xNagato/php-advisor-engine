# Auto-Approval for Small Party Bookings

## Overview

The auto-approval system automatically confirms venue bookings for small parties (7 guests or fewer) when the venue uses an integrated booking platform (Restoo or CoverManager) and the booking is successfully synchronized to that platform.

This feature reduces manual intervention for smaller bookings while maintaining the existing approval process for larger parties that require more attention.

## How It Works

### Conditions for Auto-Approval

A booking is automatically approved when **ALL** of the following conditions are met:

1. **Party Size**: 7 guests or fewer
2. **Platform Integration**: Venue has at least one enabled platform (Restoo or CoverManager)
3. **Successful API Sync**: The booking was successfully synchronized to the venue's platform
4. **Not Already Confirmed**: The booking doesn't already have a `venue_confirmed_at` timestamp

### Workflow

1. **Booking Creation**: Guest creates a booking through normal process
2. **Booking Confirmation**: Booking moves to `CONFIRMED` status
3. **Platform Sync**: System attempts to sync booking to venue's platforms
4. **Auto-Approval Check**: If sync succeeds and conditions are met, booking is auto-approved
5. **Notification**: Venue receives auto-approval notification (replaces manual confirmation request)
6. **Fallback**: If auto-approval fails, standard manual confirmation process continues

## Technical Implementation

### Key Components

- **`AutoApproveSmallPartyBooking`** - Main action class that handles auto-approval logic
- **`SendAutoApprovalNotificationToVenueContacts`** - Sends notifications for auto-approved bookings
- **`VenueContactBookingAutoApproved`** - Notification class with custom messaging
- **`BookingPlatformSyncListener`** - Enhanced to trigger auto-approval after successful sync

### API Usage

The auto-approval action is called from the platform sync listener after successful sync:

```php
// Called only when platform sync has already succeeded
$autoApproved = AutoApproveSmallPartyBooking::run($booking);
```

**Note**: The action assumes platform sync has already succeeded since it's only called from the sync listener in success scenarios. No separate platform sync status parameter is needed.

### Database Changes

No database schema changes are required. The system uses the existing `venue_confirmed_at` field on the `bookings` table.

### SMS Templates

Two new SMS templates are added to `SmsTemplates.php`:

- `venue_contact_booking_auto_approved` - Basic auto-approval notification
- `venue_contact_booking_auto_approved_notes` - Auto-approval notification with special notes

### Platform Support

Auto-approval works with:
- **Restoo** - Requires successful API response with `uuid` field
- **CoverManager** - Requires successful API response with `id_reserv` field

## Notifications

### Venue Notifications

Auto-approved bookings **replace** the standard manual confirmation flow entirely:
- Venues receive auto-approval notifications instead of confirmation requests
- Messages clearly indicate the booking was automatically approved
- Explain that the reservation is already in their system
- Don't include a confirmation link (since already confirmed)
- Include all standard booking details
- No follow-up manual confirmation is required or sent


## Logging and Monitoring

### Activity Logs

Auto-approved bookings create activity log entries with:
- `auto_approved: true` flag
- Party size information
- Venue platform details
- Timestamp of approval

### Application Logs

Detailed logging includes:
- Auto-approval attempts and results
- Platform sync success/failure details
- Qualification checks for each booking
- Error handling for failed auto-approvals

## Configuration

### Party Size Limit

The maximum party size for auto-approval is defined in `AutoApproveSmallPartyBooking::MAX_AUTO_APPROVAL_PARTY_SIZE` (currently 7).

### Platform Requirements

Venues must have:
- At least one enabled platform (`is_enabled = true`)
- Platform type must be `restoo` or `covermanager`
- Valid platform configuration

## Fallback Behavior

If auto-approval fails for any reason:
- The system logs the failure reason
- The booking continues with the standard manual confirmation process
- Venue receives normal confirmation request (not auto-approval notification)
- No impact on existing functionality
- Manual confirmation flow proceeds as normal

## Security Considerations

- Auto-approval only occurs after verified API success
- Strict party size validation prevents large party auto-approval
- Comprehensive logging for audit trails
- Graceful degradation to manual process

## Troubleshooting

### Common Issues

1. **Booking not auto-approved despite small party size**
   - Check if venue has enabled platforms
   - Verify platform API sync succeeded
   - Review application logs for specific failure reasons

2. **Platform sync fails**
   - Check platform configuration (API keys, restaurant IDs)
   - Verify platform service availability
   - Review platform-specific error logs

3. **Notifications not sent**
   - Verify venue contacts have `use_for_reservations = true`
   - Check SMS/email delivery logs
   - Confirm notification queue processing

### Log Locations

- Auto-approval attempts: Application logs with `booking_id` context
- Platform sync: Detailed in `PlatformReservation` model methods
- Notification delivery: Laravel notification system logs

## Future Enhancements

Potential improvements could include:
- Configurable party size limits per venue
- Time-based rules (e.g., different limits for prime vs non-prime hours)
- Integration with additional booking platforms
- Enhanced reporting and analytics for auto-approval rates

## Related Documentation

- [Booking Platforms](booking_platforms.md)
- [Unified Platform Reservations](unified_platform_reservations.md)
- [CoverManager Integration](covermanager_integration.md)