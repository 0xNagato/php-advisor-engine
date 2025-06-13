# Venue Endpoint

## Overview
This endpoint provides a list of active venues in the user's region. For concierge users, the list is filtered to only include venues they are allowed to access.

## Request
- **Method:** GET
- **URL:** `/api/venues`
- **Authentication:** Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters
No parameters required.

### Example Request
```bash
curl -X GET \
  https://api.example.com/api/venues \
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
      "id": 1,
      "name": "Restaurant A"
    },
    {
      "id": 2,
      "name": "Restaurant B"
    },
    {
      "id": 3,
      "name": "Restaurant C"
    }
  ]
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| data | array | List of venues |
| data[].id | integer | The unique identifier for the venue |
| data[].name | string | The name of the venue |

### Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Notes
- The venues are filtered based on the user's region, which is determined from their profile
- For concierge users, the venues are further filtered to only include venues they are allowed to access
- Only active venues are included in the response
- The venues are ordered alphabetically by name
- This endpoint returns minimal venue information (ID and name only) for use in dropdowns and selectors
- For detailed venue information, use the Reservation Hub or Availability Calendar endpoints
