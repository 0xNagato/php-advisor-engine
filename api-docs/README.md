# Concierge API Documentation

## Overview

This documentation provides detailed information about the Concierge API, which allows clients to interact with the
Concierge booking system programmatically. The API provides endpoints for managing bookings, venues, regions, and other
resources.

## Authentication

The API uses Laravel Sanctum for authentication. Most endpoints require authentication with a valid API token.
See [Authentication](Authentication.md) for details on how to authenticate with the API.

## Available Endpoints

### Public Endpoints

These endpoints do not require authentication:

- [App Config](Endpoints/AppConfig.md) - Get application configuration
- [VIP Session](Endpoints/VipSession.md) - Create and validate VIP session tokens
- [Region](Endpoints/Region.md) - Get regions
- [Neighborhood](Endpoints/Neighborhood.md) - Get neighborhoods
- [Cuisine](Endpoints/Cuisine.md) - Get cuisines
- [Specialty](Endpoints/Specialty.md) - Get specialties
- [Timeslot](Endpoints/Timeslot.md) - Get timeslots

### Protected Endpoints

These endpoints require authentication:

- [Availability Calendar](Endpoints/AvailabilityCalendar.md) - Get availability calendar
- [Booking](Endpoints/Booking.md) - Create, update, Complete, Invoice Status, Email Invoice, and delete bookings
- [Contact Form](Endpoints/ContactForm.md) - Submit contact form
- [Me](Endpoints/Me.md) - Get current user information
- [Region](Endpoints/Region.md) - Get regions and update user's region
- [Reservation Hub](Endpoints/ReservationHub.md) - Get reservation hub information
- [Role Profile](Endpoints/RoleProfile.md) - Get role profiles and switch between them
- [Update Push Token](Endpoints/UpdatePushToken.md) - Update push notification token
- [Venue](Endpoints/Venue.md) - Get venues and show venue details

### Admin Endpoints

These endpoints require authentication and may have additional permission requirements:

- [VIP Session Analytics](Endpoints/VipSession.md#vip-session-analytics) - Get VIP session analytics and statistics

## Data Models

For information about the data models used in the API, see the [Models](Models/README.md) documentation.

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of a request. In case of an error, the
response will include a JSON object with a `message` field describing the error. Some responses may also include an
`errors` field with detailed validation errors.

Common status codes:

- 200 OK - The request was successful
- 201 Created - The resource was successfully created
- 204 No Content - The request was successful but there is no content to return
- 400 Bad Request - The request was invalid
- 401 Unauthorized - Authentication is required
- 403 Forbidden - The authenticated user does not have permission to access the resource
- 404 Not Found - The requested resource was not found
- 422 Unprocessable Entity - The request was well-formed but could not be processed due to semantic errors (e.g.,
  validation errors)
- 500 Internal Server Error - An error occurred on the server
