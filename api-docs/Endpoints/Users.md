# Users Endpoint

## Overview

This endpoint retrieves a list of all users along with their associated concierge and partner profiles. Access is restricted to users with the 'super_admin' role.

## Request

- **Method:** GET
- **URL:** `/api/users`
- **Authentication:** Required (Sanctum)
- **Authorization:** Super Admin role required

### Headers

| Header        | Value            | Required | Description                              |
|---------------|------------------|----------|------------------------------------------|
| Accept        | application/json | Yes      | Specifies the expected response format   |
| Authorization | Bearer {token}   | Yes      | The Sanctum authentication token         |

### Query Parameters

No query parameters are required for this endpoint.

### Example Request

```
curl -X GET \
https://api.example.com/api/users \
-H 'Accept: application/json' \
-H 'Authorization: Bearer 1|aBcDeFgHiJkLmNoPqRsTuVwXyZ'
```

## Success Response

- **Status Code**: 200 OK

### Response Body

The response is a paginated list of users with their associated concierge and partner profiles.

```
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "admin@example.com",
      "role": "super_admin",
      "avatar": "https://example.com/avatars/1.jpg",
      "timezone": "America/New_York",
      "region": 1,
      "concierge": null,
      "partner": null,
      "roles": [
        {
          "id": 1,
          "name": "super_admin",
          "pivot": {
            "user_id": 1,
            "role_id": 1
          }
        }
      ]
    },
    {
      "id": 2,
      "name": "Jane Smith",
      "email": "concierge@example.com",
      "role": "concierge",
      "avatar": "https://example.com/avatars/2.jpg",
      "timezone": "America/Los_Angeles",
      "region": 2,
      "concierge": {
        "id": 1,
        "user_id": 2,
        "bio": "Experienced concierge with 5 years in luxury hospitality."
      },
      "partner": null,
      "roles": [
        {
          "id": 2,
          "name": "concierge",
          "pivot": {
            "user_id": 2,
            "role_id": 2
          }
        }
      ]
    }
  ],
  "first_page_url": "https://api.example.com/api/users?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "https://api.example.com/api/users?page=5",
  "links": [
    {
      "url": null,
      "label": "&laquo; Previous",
      "active": false
    },
    {
      "url": "https://api.example.com/api/users?page=1",
      "label": "1",
      "active": true
    },
    {
      "url": "https://api.example.com/api/users?page=2",
      "label": "2",
      "active": false
    },
    {
      "url": "https://api.example.com/api/users?page=2",
      "label": "Next &raquo;",
      "active": false
    }
  ],
  "next_page_url": "https://api.example.com/api/users?page=2",
  "path": "https://api.example.com/api/users",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 75
}
```

## Error Responses

- **Status Code**: 401 Unauthorized
  - Returned when the request is made without a valid authentication token.

```
{
  "message": "Unauthenticated."
}
```

- **Status Code**: 403 Forbidden
  - Returned when the authenticated user does not have the 'super_admin' role.

```
{
  "message": "Unauthorized action."
}
```
