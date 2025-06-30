# Role Profile Endpoint

## Overview
This endpoint provides functionality for retrieving a user's role profiles and switching between them. Role profiles allow users to have multiple roles in the system.

## Get Role Profiles

### Request
- **Method:** GET
- **URL:** `/api/profiles`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

#### Parameters
No parameters required.

#### Example Request
```bash
curl -X GET \
  https://api.example.com/api/profiles \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
  "profiles": [
    {
      "id": 1,
      "name": "Concierge Profile",
      "role": "concierge",
      "is_active": true
    },
    {
      "id": 2,
      "name": "Venue Manager Profile",
      "role": "venue_manager",
      "is_active": false
    }
  ]
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| profiles | array | List of role profiles for the user |
| profiles[].id | integer | The unique identifier for the role profile |
| profiles[].name | string | The name of the role profile |
| profiles[].role | string | The role associated with the profile |
| profiles[].is_active | boolean | Whether the profile is currently active |

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Switch Role Profile

### Request
- **Method:** POST
- **URL:** `/api/profiles/{profile}/switch`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| profile | integer | Yes | The ID of the role profile to switch to |

#### Example Request
```bash
curl -X POST \
  https://api.example.com/api/profiles/2/switch \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Error Response
- **Status Code:** 403 Forbidden

##### Response Body
```json
{
  "message": "Role switching is currently disabled from the mobile app, please use the web app to switch roles."
}
```

## Notes
- The GET endpoint returns all role profiles for the authenticated user
- The POST endpoint for switching roles is currently disabled in the mobile app
- Users can have multiple role profiles, but only one can be active at a time
- Role profiles determine what permissions and features are available to the user
