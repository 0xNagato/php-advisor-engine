# Booking Endpoint

## Overview
This endpoint provides functionality for creating, updating, and deleting bookings in the system.

## Create Booking

### Request
- **Method:** POST
- **URL:** `/api/bookings`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |
| Content-Type | application/json | Yes | Specifies the request format |

#### Request Body
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string (YYYY-MM-DD) | Yes | The date for the booking (must not be in the past) |
| schedule_template_id | integer | Yes | The ID of the schedule template for the booking |
| guest_count | integer | Yes | The number of guests for the booking |

#### Example Request
```bash
curl -X POST \
  https://api.example.com/api/bookings \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "date": "2023-06-15",
    "schedule_template_id": 123,
    "guest_count": 4
  }'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
    "data": {
        "bookings_enabled": true,
        "bookings_disabled_message": "Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.",
        "id": 288705,
        "guest_count": "2",
        "dayDisplay": "Sun, Jun 15 at 6:00 pm",
        "status": "pending",
        "venue": "Gekko",
        "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/gekko.png",
        "total": "$0.00",
        "subtotal": "$0.00",
        "tax_rate_term": null,
        "tax_amount": null,
        "bookingUrl": "http://localhost:8000/checkout/ba52e84f-9dd2-41e1-a80f-d928ac2e5a6d?r=sms",
        "qrCode": "data:image/svg+xml;base64,PD94b...",
        "is_prime": 0,
        "booking_at": "2025-06-15T18:00:00.000000Z"
    }
}
```

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

##### 404 Not Found
```json
{
  "message": "Booking failed"
}
```

##### 422 Unprocessable Entity
```json
{
  "date": [
    "The date field is required.",
    "The date must not be in the past."
  ],
  "schedule_template_id": [
    "The schedule template id field is required."
  ],
  "guest_count": [
    "The guest count field is required."
  ]
}
```

## Update Booking

### Request
- **Method:** PUT
- **URL:** `/api/bookings/{booking}`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |
| Content-Type | application/json | Yes | Specifies the request format |

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| booking | integer | Yes | The ID of the booking to update |

#### Request Body
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| first_name | string | Yes | The first name of the guest (max: 255 characters) |
| last_name | string | Yes | The last name of the guest (max: 255 characters) |
| phone | string | Yes | The phone number of the guest (must be a valid phone number) |
| email | string | No | The email address of the guest (must be a valid email) |
| notes | string | No | Additional notes for the booking (max: 1000 characters) |
| bookingUrl | string | Yes | The URL for the booking payment form |

#### Example Request
```bash
curl -X PUT \
  https://api.example.com/api/bookings/456 \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1 (555) 123-4567",
    "email": "john.doe@example.com",
    "notes": "Allergic to nuts",
    "bookingUrl": "https://example.com/booking/456/payment"
  }'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
  "message": "SMS Message Sent Successfully"
}
```

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

##### 404 Not Found
```json
{
  "message": "Booking already confirmed or cancelled"
}
```

##### 422 Unprocessable Entity
```json
{
  "message": "Venue is not currently accepting bookings"
}
```

or

```json
{
  "message": "Customer already has a non-prime booking for this day"
}
```

or

```json
{
  "first_name": [
    "The first name field is required."
  ],
  "last_name": [
    "The last name field is required."
  ],
  "phone": [
    "The phone field must be a valid phone number."
  ],
  "bookingUrl": [
    "The booking url field is required."
  ]
}
```

## Delete Booking

### Request
- **Method:** DELETE
- **URL:** `/api/bookings/{booking}`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| booking | integer | Yes | The ID of the booking to delete |

#### Example Request
```bash
curl -X DELETE \
  https://api.example.com/api/bookings/456 \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
  "message": "Booking Abandoned"
}
```

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

##### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Booking] 456"
}
```

##### 422 Unprocessable Entity
```json
{
  "message": "Booking cannot be abandoned in its current status"
}
```

## Notes
- When creating a booking, the `schedule_template_id` should be obtained from the availability calendar endpoint
- When updating a booking, the booking must be in the "pending" status
- When deleting a booking, the booking must be in the "pending" or "guest_on_page" status
- The delete operation doesn't actually delete the booking from the database, but changes its status to "abandoned"
- For non-prime bookings, a customer can only have one booking per day at a venue
