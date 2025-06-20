# Reservation Hub Endpoint

## Overview
This endpoint provides detailed schedule information for a specific venue, including available timeslots and their status. It's used to display the reservation hub interface for a selected venue.

## Request
- **Method:** GET
- **URL:** `/api/hub`
- **Authentication:** Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string (YYYY-MM-DD) | Yes | The date for which to check venue schedules |
| guest_count | integer | Yes | The number of guests for the reservation (minimum: 1) |
| reservation_time | string (HH:MM:SS) | Yes | The time for the reservation |
| venue_id | integer | Yes | The ID of the venue to get schedules for |
| timeslot_count | integer | No | The number of timeslots to return (default: 5, min: 1, max: 10) |
| time_slot_offset | integer | No | The offset for timeslots (default: 1, min: 0, max: 10) |

### Example Request
```bash
curl -X GET \
  'https://api.example.com/api/hub?date=2023-06-15&guest_count=4&reservation_time=19:00:00&venue_id=1&timeslot_count=5' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

## Response

### Success Response
- **Status Code:** 200 OK

#### Response Body
```json
{
    "data": [
        {
            "schedulesByDate": [
                {
                    "id": 248947,
                    "schedule_template_id": 248947,
                    "is_bookable": false,
                    "prime_time": false,
                    "time": {
                        "value": "2:00 PM",
                        "raw": "14:00:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$0",
                    "has_low_inventory": false
                },
                {
                    "id": 248952,
                    "schedule_template_id": 248952,
                    "is_bookable": false,
                    "prime_time": false,
                    "time": {
                        "value": "2:30 PM",
                        "raw": "14:30:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$0",
                    "has_low_inventory": false
                },
                {
                    "id": 248957,
                    "schedule_template_id": 248957,
                    "is_bookable": false,
                    "prime_time": true,
                    "time": {
                        "value": "3:00 PM",
                        "raw": "15:00:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$100",
                    "has_low_inventory": false
                },
                {
                    "id": 248962,
                    "schedule_template_id": 248962,
                    "is_bookable": false,
                    "prime_time": true,
                    "time": {
                        "value": "3:30 PM",
                        "raw": "15:30:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$100",
                    "has_low_inventory": false
                },
                {
                    "id": 248967,
                    "schedule_template_id": 248967,
                    "is_bookable": false,
                    "prime_time": true,
                    "time": {
                        "value": "4:00 PM",
                        "raw": "16:00:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$100",
                    "has_low_inventory": false
                }
            ],
            "schedulesThisWeek": [
                {
                    "id": 248707,
                    "schedule_template_id": 248707,
                    "is_bookable": false,
                    "prime_time": false,
                    "time": {
                        "value": "2:00 PM",
                        "raw": "14:00:00"
                    },
                    "date": "2025-06-14",
                    "fee": "$0",
                    "has_low_inventory": false
                },
                {
                    "id": 248947,
                    "schedule_template_id": 248947,
                    "is_bookable": false,
                    "prime_time": false,
                    "time": {
                        "value": "2:00 PM",
                        "raw": "14:00:00"
                    },
                    "date": "2025-06-15",
                    "fee": "$0",
                    "has_low_inventory": false
                },
                {
                    "id": 247507,
                    "schedule_template_id": 247507,
                    "is_bookable": false,
                    "prime_time": false,
                    "time": {
                        "value": "2:00 PM",
                        "raw": "14:00:00"
                    },
                    "date": "2025-06-16",
                    "fee": "$0",
                    "has_low_inventory": false
                }
            ]
        }
    ]
}
```

#### Response Fields
| Field                                    | Type    | Description                                                  |
|------------------------------------------|---------|--------------------------------------------------------------|
| data[]                                   | array   | Array containing the schedules for venues.                  |
| data[].schedulesByDate[]                 | array   | Array containing schedules for the requested date.          |
| data[].schedulesByDate[].id              | integer | The unique identifier for the schedule for the requested date. |
| data[].schedulesByDate[].schedule_template_id | integer | The ID of the schedule template needed for booking.          |
| data[].schedulesByDate[].is_bookable     | boolean | Indicates whether the schedule is bookable.                 |
| data[].schedulesByDate[].prime_time      | boolean | Indicates whether the schedule is a prime-time slot.        |
| data[].schedulesByDate[].time.value      | string  | Human-readable format of the time (e.g., "2:00 PM").         |
| data[].schedulesByDate[].time.raw        | string  | Raw time format (HH:MM:SS).                                 |
| data[].schedulesByDate[].date            | string  | The date of the schedule (YYYY-MM-DD).                      |
| data[].schedulesByDate[].fee             | string  | The fee associated with the schedule.                       |
| data[].schedulesByDate[].has_low_inventory | boolean | Indicates whether the schedule has limited availability.    |
| data[].schedulesThisWeek[]               | array   | Array containing schedules for the current week.            |
| data[].schedulesThisWeek[].id            | integer | The unique identifier for the schedule for the current week.|
| data[].schedulesThisWeek[].schedule_template_id | integer | The ID of the schedule template needed for booking.          |
| data[].schedulesThisWeek[].is_bookable   | boolean | Indicates whether the schedule is bookable.                 |
| data[].schedulesThisWeek[].prime_time    | boolean | Indicates whether the schedule is a prime-time slot.        |
| data[].schedulesThisWeek[].time.value    | string  | Human-readable format of the time (e.g., "2:00 PM").         |
| data[].schedulesThisWeek[].time.raw      | string  | Raw time format (HH:MM:SS).                                 |
| data[].schedulesThisWeek[].date          | string  | The date of the schedule (YYYY-MM-DD).                      |
| data[].schedulesThisWeek[].fee           | string  | The fee associated with the schedule.                       |
| data[].schedulesThisWeek[].has_low_inventory | boolean | Indicates whether the schedule has limited availability.    |

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
  ],
  "venue_id": [
    "The venue id field is required."
  ]
}
```

or

```json
{
  "venue_id": [
    "The selected venue id is invalid."
  ]
}
```

## Notes
- This endpoint is similar to the Availability Calendar endpoint but focuses on a single venue
- The schedule template IDs returned in the response are needed when creating a booking
- The `remaining` field indicates how many more reservations can be made for a particular timeslot
- Timeslots with `available: false` are fully booked or otherwise unavailable
