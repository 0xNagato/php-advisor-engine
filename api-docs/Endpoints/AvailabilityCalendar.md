# Availability Calendar Endpoint

## Overview
This endpoint provides information about available venues and timeslots for a specific date, guest count, and reservation time. It's used to display the availability calendar in the booking interface.

## Request
- **Method:** GET
- **URL:** `/api/calendar`
- **Authentication:** Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string (YYYY-MM-DD) | Yes | The date for which to check availability |
| guest_count | integer | Yes | The number of guests for the reservation (minimum: 1) |
| reservation_time | string (HH:MM:SS) | Yes | The time for the reservation |
| timeslot_count | integer | No | The number of timeslots to return (default: 5, min: 1, max: 20) |
| time_slot_offset | integer | No | The offset for timeslots (default: 1) |

### Example Request
```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00&timeslot_count=5' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

## Response

### Success Response
- **Status Code:** 200 OK

#### Response Body
```json
{
  "data": {
    "venues": [
      {
        "id": 1,
        "name": "Restaurant A",
        "description": "A fine dining restaurant",
        "address": "123 Main St",
        "city": "New York",
        "state": "NY",
        "zip": "10001",
        "phone": "+1 (555) 123-4567",
        "email": "info@restauranta.com",
        "website": "https://www.restauranta.com",
        "image_url": "https://example.com/images/restaurant-a.jpg",
        "availability": [
          {
            "time": "18:30:00",
            "available": true,
            "schedule_template_id": 123
          },
          {
            "time": "19:00:00",
            "available": true,
            "schedule_template_id": 124
          },
          {
            "time": "19:30:00",
            "available": false,
            "schedule_template_id": null
          }
        ]
      },
      {
        "id": 2,
        "name": "Restaurant B",
        "description": "A casual dining restaurant",
        "address": "456 Broadway",
        "city": "New York",
        "state": "NY",
        "zip": "10002",
        "phone": "+1 (555) 987-6543",
        "email": "info@restaurantb.com",
        "website": "https://www.restaurantb.com",
        "image_url": "https://example.com/images/restaurant-b.jpg",
        "availability": [
          {
            "time": "18:30:00",
            "available": false,
            "schedule_template_id": null
          },
          {
            "time": "19:00:00",
            "available": true,
            "schedule_template_id": 125
          },
          {
            "time": "19:30:00",
            "available": true,
            "schedule_template_id": 126
          }
        ]
      }
    ],
    "timeslots": [
      "18:30:00",
      "19:00:00",
      "19:30:00",
      "20:00:00",
      "20:30:00"
    ]
  }
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| data.venues | array | List of venues with their availability |
| data.venues[].id | integer | Unique identifier for the venue |
| data.venues[].name | string | Name of the venue |
| data.venues[].description | string | Description of the venue |
| data.venues[].address | string | Street address of the venue |
| data.venues[].city | string | City where the venue is located |
| data.venues[].state | string | State where the venue is located |
| data.venues[].zip | string | ZIP code of the venue |
| data.venues[].phone | string | Contact phone number for the venue |
| data.venues[].email | string | Contact email for the venue |
| data.venues[].website | string | Website URL for the venue |
| data.venues[].image_url | string | URL of the venue's image |
| data.venues[].availability | array | List of availability slots for the venue |
| data.venues[].availability[].time | string | Time slot (HH:MM:SS) |
| data.venues[].availability[].available | boolean | Whether the time slot is available |
| data.venues[].availability[].schedule_template_id | integer\|null | ID of the schedule template if available, null otherwise |
| data.timeslots | array | List of time slots for the requested date |

### Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 422 Unprocessable Entity
```json
{
  "date": [
    "The date field is required."
  ],
  "guest_count": [
    "The guest count field is required."
  ],
  "reservation_time": [
    "The reservation time field is required."
  ]
}
```

## Notes
- The response includes both a list of venues with their availability and a list of time slots
- The `schedule_template_id` is needed when creating a booking for a specific time slot
- Time slots are returned in chronological order
- The number of time slots returned can be controlled with the `timeslot_count` parameter
