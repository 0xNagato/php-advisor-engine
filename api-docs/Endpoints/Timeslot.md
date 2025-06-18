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
| region | string | No | Region ID to override the concierge's default region and get timeslots for the specified region timezone. Valid values: `miami`, `ibiza`, `mykonos`, `paris`, `london`, `st_tropez`, `new_york`, `los_angeles`, `las_vegas` |

### Example Requests

#### Basic Request (uses concierge's default region)

```bash
curl -X GET \
  'https://api.example.com/api/timeslots?date=2023-06-15' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Request with Region Override

```bash
curl -X GET \
  'https://api.example.com/api/timeslots?date=2023-06-15&region=paris' \
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
  ],
  "region": [
    "The selected region is invalid."
  ]
}
```

## Notes

- If the date is in the past or invalid, all timeslots will be returned with `available: false`
- For the current day, timeslots that have already passed will have `available: false`
- The `value` field should be used when creating a booking

### Region Parameter Behavior

- **Default Behavior**: When no `region` parameter is provided, timeslots are calculated using the authenticated concierge's default region timezone
- **Region Override**: When a `region` parameter is specified, timeslots are calculated using the specified region's timezone
- **Timezone Impact**: The "current day" check and availability calculations are based on the region's timezone
- **Same Day Logic**: For same-day reservations, timeslots that have already passed in the specified region's timezone will have `available: false`

### Valid Region Values

| Region ID | Name | Timezone |
|-----------|------|----------|
| `miami` | Miami | America/New_York |
| `ibiza` | Ibiza | Europe/Madrid |
| `mykonos` | Mykonos | Europe/Athens |
| `paris` | Paris | Europe/Paris |
| `london` | London | Europe/London |
| `st_tropez` | St. Tropez | Europe/Paris |
| `new_york` | New York | America/New_York |
| `los_angeles` | Los Angeles | America/Los_Angeles |
| `las_vegas` | Las Vegas | America/Los_Angeles |
