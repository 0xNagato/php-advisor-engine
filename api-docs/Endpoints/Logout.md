# Logout Endpoint

## Overview

This endpoint logs out a user by revoking their current access token. It requires authentication with a valid Sanctum token.

## Request

- **Method:** POST
- **URL:** `/api/logout`
- **Authentication:** Required (Sanctum)

### Headers

| Header        | Value            | Required | Description                              |
|---------------|------------------|----------|------------------------------------------|
| Accept        | application/json | Yes      | Specifies the expected response format   |
| Content-Type  | application/json | Yes      | Specifies the format of the request body |
| Authorization | Bearer {token}   | Yes      | The Sanctum authentication token         |

### Body Parameters

No body parameters are required for this endpoint.

### Example Request

```
curl -X POST \
https://api.example.com/api/logout \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-H 'Authorization: Bearer 1|aBcDeFgHiJkLmNoPqRsTuVwXyZ'
```

## Success Response

- **Status Code**: 200 OK

### Response Body

```
{
  "message": "Successfully logged out."
}
```

## Error Responses

- **Status Code**: 401 Unauthorized
  - Returned when the request is made without a valid authentication token.

### Error Response Body

```
{
  "message": "Unauthenticated."
}
```
