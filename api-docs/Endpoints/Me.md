# Me Endpoint

## Overview
This endpoint returns information about the authenticated user, including their ID, role, email, name, avatar, timezone, and region.

## Request
- **Method:** GET
- **URL:** `/api/me`
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
  https://api.example.com/api/me \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

## Response

### Success Response
- **Status Code:** 200 OK

#### Response Body
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "role": "concierge",
      "email": "user@example.com",
      "name": "John Doe",
      "avatar": "https://example.com/avatars/john-doe.jpg",
      "timezone": "America/New_York",
      "region": "miami"
    }
  }
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Indicates whether the request was successful |
| data.user.id | integer | The unique identifier for the user |
| data.user.role | string | The user's main role (in snake_case format) |
| data.user.email | string | The user's email address |
| data.user.name | string | The user's full name |
| data.user.avatar | string | URL to the user's avatar image |
| data.user.timezone | string | The user's timezone |
| data.user.region | string | The ID of the user's selected region |

### Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Notes
- This endpoint is useful for getting information about the currently authenticated user
- The user's role is returned in snake_case format (e.g., "concierge", "admin", "venue_manager")
- The region field contains the ID of the user's selected region, which can be used with the Region endpoint
