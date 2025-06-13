# Timeslot Endpoint

## Overview
This endpoint provides a list of available timeslots for a given date. It's used to display the available reservation times in the booking interface.

## Request
- **Method:** GET
- **URL:** `/api/timeslots`
- **Authentication:** Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string (YYYY-MM-DD) | Yes | The date for which to get available timeslots |

### Example Request
```bash
curl -X GET \
  'https://api.example.com/api/timeslots?date=2023-06-15' \
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
      "label": "5:00 PM",
      "value": "17:00:00",
      "available": true
    },
    {
      "label": "5:30 PM",
      "value": "17:30:00",
      "available": true
    },
    {
      "label": "6:00 PM",
      "value": "18:00:00",
      "available": true
    },
    {
      "label": "6:30 PM",
      "value": "18:30:00",
      "available": true
    },
    {
      "label": "7:00 PM",
      "value": "19:00:00",
      "available": true
    },
    {
      "label": "7:30 PM",
      "value": "19:30:00",
      "available": true
    },
    {
      "label": "8:00 PM",
      "value": "20:00:00",
      "available": true
    },
    {
      "label": "8:30 PM",
      "value": "20:30:00",
      "available": true
    },
    {
      "label": "9:00 PM",
      "value": "21:00:00",
      "available": true
    },
    {
      "label": "9:30 PM",
      "value": "21:30:00",
      "available": true
    }
  ]
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| data | array | List of timeslots |
| data[].label | string | The formatted time (e.g., "7:00 PM") |
| data[].value | string | The time in 24-hour format (HH:MM:SS) |
| data[].available | boolean | Whether the timeslot is available |

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
  ]
}
```

## Notes
- If the date is in the past or invalid, all timeslots will be returned with `available: false`
- For the current day, timeslots that have already passed will have `available: false`
- The timeslots are based on the user's region timezone
- The `value` field should be used when creating a booking
