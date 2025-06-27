# CoverManager Integration

## Overview

CoverManager integration allows PRIMA to synchronize venue availability and reservations with the CoverManager restaurant reservation system. This document covers how to set up and use the integration.

## Configuration

### Environment Variables

Add the following variables to your `.env` file:

```env
COVERMANAGER_API_KEY=your_api_key
COVERMANAGER_BASE_URL=https://beta.covermanager.com/api
COVERMANAGER_ENVIRONMENT=beta
```

For production use, change the environment to `production` and update the base URL accordingly.

## Linking Venues with CoverManager

1. Navigate to **Integrations > CoverManager** in the admin panel
2. Each venue must be linked with a corresponding restaurant in CoverManager:
   - Toggle **Enable CoverManager** to on
   - Enter the **CoverManager Restaurant ID** (obtained from CoverManager)
   - Toggle **Enable Automatic Sync** if you want automatic synchronization

## Features

### Availability Synchronization

Availability is synchronized between PRIMA and CoverManager in two ways:

1. **Manual sync**: Click the "Sync Now" button for a specific venue
2. **Automatic sync**: Run the scheduled command `app:sync-covermanager-availability`

```bash
# Sync all venues
php artisan app:sync-covermanager-availability

# Sync a specific venue
php artisan app:sync-covermanager-availability --venue-id=123

# Specify number of days to sync
php artisan app:sync-covermanager-availability --days=14
```

### Reservation Management

#### Creating Reservations

When a booking is confirmed in PRIMA, it is automatically synchronized to CoverManager. This happens through an event listener that responds to the `BookingConfirmed` event.

#### Cancelling Reservations

Reservations are automatically cancelled in CoverManager when they are cancelled in PRIMA through the `CoverManagerBookingCancellationListener`. This happens when the `BookingCancelled` event is fired.

You can also manually manage reservations using the unified model:

```php
// Using the new unified model (Recommended)
$reservation = PlatformReservation::createFromBooking($booking, 'covermanager');
$success = $reservation->syncToPlatform();
$cancelled = $reservation->cancelInPlatform();

// Or using the CoverManager service directly
$coverManagerService = app(\App\Services\CoverManagerService::class);
$success = $coverManagerService->cancelReservation(
    $venue->covermanager_id, 
    $reservation->platform_reservation_id
);
```

## Architecture

### Database Models (Updated June 2025)

- **Venue**: Contains legacy CoverManager fields (backward compatible)
- **VenuePlatform**: New platform configuration model with `platform_type: 'covermanager'`
- **PlatformReservation**: **Unified reservation model** that handles CoverManager and all other platforms ✅ **NEW**
- ~~**CoverManagerReservation**~~: **DEPRECATED** - replaced by PlatformReservation (will be removed post-migration)

### Services

- **CoverManagerService**: Handles all API communication with CoverManager
- Available methods:
  - `getRestaurants(string $city)`: Get list of restaurants by city
  - `getRestaurantData(string $restaurantId)`: Get details for a specific restaurant
  - `checkAvailability(string $restaurantId, Carbon $date, string $time, int $partySize)`: Check availability
  - `createReservation(string $restaurantId, array $bookingData)`: Create a reservation
  - `cancelReservation(string $restaurantId, string $reservationId)`: Cancel a reservation

### Commands

- **SyncCoverManagerAvailability**: Synchronizes venue availability with CoverManager

### Event Listeners (Updated June 2025)

- **BookingPlatformSyncListener**: **Unified listener** that syncs bookings to all enabled platforms including CoverManager ✅ **NEW**
- **BookingPlatformCancellationListener**: **Unified listener** that cancels reservations across all platforms including CoverManager ✅ **NEW**
- ~~**CoverManagerBookingListener**~~: **DEPRECATED** - replaced by unified listeners
- ~~**CoverManagerBookingCancellationListener**~~: **DEPRECATED** - replaced by unified listeners

## Troubleshooting

### Logs

All CoverManager API interactions are logged with detailed information:

- Successful operations are logged at INFO level
- Errors are logged at ERROR level with full request/response details
- Debug information is available at DEBUG level

Check your Laravel logs for entries with `CoverManager` in the message.

### Common Issues

1. **Authentication failures**: Check your API key is correct
2. **Restaurant not found**: Verify the restaurant ID exists in CoverManager
3. **Invalid availability data**: Ensure time formats match (CoverManager uses HH:MM format)

## API Endpoints Reference

| Endpoint | Description |
|----------|-------------|
| `GET /restaurant/list/{api_key}/{city}` | List restaurants by city |
| `GET /restaurant/get/{api_key}/{restaurant_id}` | Get restaurant details |
| `GET /availability/get/{api_key}/{restaurant_id}` | Check availability |
| `POST /reservation/create/{api_key}/{restaurant_id}` | Create a reservation |
| `DELETE /reservation/cancel/{api_key}/{restaurant_id}/{reservation_id}` | Cancel a reservation |
