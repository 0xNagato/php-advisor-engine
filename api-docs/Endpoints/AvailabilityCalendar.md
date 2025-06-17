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
    | reservation_time | string (HH:MM:SS) | Yes | The time for the reservation. If the reservation is for the current day, the time must be at least 30 minutes from the current time. |
| timeslot_count | integer | No | The number of timeslots to return (default: 5, min: 1, max: 20) |
| time_slot_offset | integer | No | The offset for timeslots (default: 1) |
| cuisine | array | No | Filter venues by cuisine types |
| neighborhood | string | No | Filter venues by neighborhood |
| specialty | array | No | Filter venues by specialty types |

### Example Request
```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00&timeslot_count=5&cuisine[]=italian&cuisine[]=japanese&neighborhood=downtown&specialty[]=steak' \
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
        "id": 76,
        "name": "Call Me Gaby",
        "status": "active",
        "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/call_me_gaby.png",
        "non_prime_time": null,
        "business_hours": null,
        "tier": null,
        "tier_label": "Standard",
        "schedules": [
          {
            "id": 102882,
            "schedule_template_id": 102882,
            "is_bookable": false,
            "prime_time": true,
            "time": {
              "value": "4:00 PM",
              "raw": "16:00:00"
            },
            "date": "2025-06-17",
            "fee": "$100",
            "has_low_inventory": false
          },
          {
            "id": 102887,
            "schedule_template_id": 102887,
            "is_bookable": false,
            "prime_time": true,
            "time": {
              "value": "4:30 PM",
              "raw": "16:30:00"
            },
            "date": "2025-06-17",
            "fee": "$100",
            "has_low_inventory": false
          },
          {
            "id": 102892,
            "schedule_template_id": 102892,
            "is_bookable": true,
            "prime_time": true,
            "time": {
              "value": "5:00 PM",
              "raw": "17:00:00"
            },
            "date": "2025-06-17",
            "fee": "$100",
            "has_low_inventory": true
          }
        ]
      },
      {
        "id": 63,
        "name": "Mandolin",
        "status": "active",
        "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/mandolin.png",
        "non_prime_time": null,
        "business_hours": null,
        "tier": null,
        "tier_label": "Standard",
        "schedules": [
          {
            "id": 81042,
            "schedule_template_id": 81042,
            "is_bookable": true,
            "prime_time": true,
            "time": {
              "value": "4:00 PM",
              "raw": "16:00:00"
            },
            "date": "2025-06-17",
            "fee": "$200",
            "has_low_inventory": true
          },
          {
            "id": 81047,
            "schedule_template_id": 81047,
            "is_bookable": true,
            "prime_time": true,
            "time": {
              "value": "4:30 PM",
              "raw": "16:30:00"
            },
            "date": "2025-06-17",
            "fee": "$200",
            "has_low_inventory": true
          }
        ]
      }
    ],
    "timeslots": [
      "4:00 PM",
      "4:30 PM",
      "5:00 PM",
      "5:30 PM",
      "6:00 PM"
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
| data.venues[].status | string | Status of the venue (active, pending, etc.) |
| data.venues[].logo | string | URL of the venue's logo |
| data.venues[].non_prime_time | string\|null | Non-prime time information for the venue |
| data.venues[].business_hours | string\|null | Business hours information for the venue |
| data.venues[].tier | integer\|null | Tier level of the venue |
| data.venues[].tier_label | string | Human-readable label for the venue's tier |
| data.venues[].schedules | array | List of schedule slots for the venue |
| data.venues[].schedules[].id | integer | Unique identifier for the schedule |
| data.venues[].schedules[].schedule_template_id | integer | ID of the schedule template needed for booking |
| data.venues[].schedules[].is_bookable | boolean | Whether the time slot is available for booking |
| data.venues[].schedules[].prime_time | boolean | Whether the time slot is during prime time |
| data.venues[].schedules[].time.value | string | Formatted time (e.g., "4:00 PM") |
| data.venues[].schedules[].time.raw | string | Raw time format (HH:MM:SS) |
| data.venues[].schedules[].date | string | Date of the schedule (YYYY-MM-DD) |
| data.venues[].schedules[].fee | string | Fee for the reservation at this time slot |
| data.venues[].schedules[].has_low_inventory | boolean | Whether there is limited availability for this time slot |
| data.timeslots | array | List of formatted time slots for the requested date |

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
    "The reservation time field is required.",
    "The reservation time must be at least 30 minutes from now."
  ]
}
```

## Notes
- The response includes both a list of venues with their availability and a list of time slots
- The `schedule_template_id` is needed when creating a booking for a specific time slot
- Time slots are returned in chronological order
- The number of time slots returned can be controlled with the `timeslot_count` parameter
- For same-day reservations, the reservation time must be at least 30 minutes from the current time
