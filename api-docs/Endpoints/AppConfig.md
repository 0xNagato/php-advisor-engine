# App Config Endpoint

## Overview
This endpoint provides application configuration data, including information about bookings status and login settings.

## Request
- **Method:** GET
- **URL:** `/api/app-config`
- **Authentication:** Not Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters
No parameters required.

### Example Request
```bash
curl -X GET \
  https://api.example.com/api/app-config \
  -H 'Accept: application/json'
```

## Response

### Success Response
- **Status Code:** 200 OK

#### Response Body
```json
{
  "bookings_enabled": true,
  "bookings_disabled_message": null,
  "max_reservation_days": 30,
  "login": {
    "background_image": "https://example.com/images/login-bg.jpg",
    "text_color": "#FFFFFF"
  }
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| bookings_enabled | boolean | Indicates whether bookings are currently enabled in the application |
| bookings_disabled_message | string\|null | Message to display when bookings are disabled, or null if bookings are enabled |
| max_reservation_days | integer | Maximum number of days in advance that reservations can be made (default: 30) |
| login.background_image | string | URL of the background image to display on the login screen |
| login.text_color | string | Hex color code for text on the login screen |

### Error Responses

#### 500 Internal Server Error
```json
{
  "message": "Server error"
}
```

## Notes
- This endpoint is cached for 1 hour (3600 seconds) to improve performance
- This is one of the few endpoints that does not require authentication
- The `max_reservation_days` field can be used by frontend applications to limit calendar date selection
- This value is configurable via the `MAX_RESERVATION_DAYS` environment variable
