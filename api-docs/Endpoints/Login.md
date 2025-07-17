# Login Endpoint

## Overview

This endpoint authenticates a user with their email and password. On success, it returns the user's details and a new
Sanctum API token for use in subsequent authenticated requests.

## Request

- **Method:** POST
- **URL:** `/api/login`
- **Authentication:** Not Required

### Headers

| Header       | Value            | Required | Description                              |
|--------------|------------------|----------|------------------------------------------|
| Accept       | application/json | Yes      | Specifies the expected response format   |
| Content-Type | application/json | Yes      | Specifies the format of the request body |

### Body Parameters

| Parameter   | Type   | Required | Description                                                                                                            |
|-------------|--------|----------|------------------------------------------------------------------------------------------------------------------------|
| email       | string | Yes      | The user's email address.                                                                                              |
| password    | string | Yes      | The user's password.                                                                                                   |
| device_name | string | Yes      | A unique name for the device or client making the request (e.g., "My iPhone App"). This is used to identify the token. |

### Example Request

````
curl -X POST \
https://api.example.com/api/login \
-H 'Content-Type: application/json' \
-H 'Accept: application/json' \
-d '{
"email": "concierge@example.com",
"password": "password",
"device_name": "Android App"
}'
````

### Success Response

- **Status Code**: 200 OK

### Response Body

````
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "concierge@example.com",
    "role": "concierge",
    "avatar": "https://example.com/avatars/1.jpg",
    "timezone": "America/New_York",
    "region": 1
  },
  "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ"
}
````

## Error Responses

- **Status Code**: 422 Unprocessable Entity
  - Returned when validation fails (missing or invalid fields).

### Validation Error Response Body

````
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ],
    "device_name": [
      "The device name field is required."
    ]
  }
}
````

- **Status Code**: 422 Unprocessable Entity
  - Returned when the provided credentials are incorrect.

### Authentication Error Response Body

````
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The provided credentials do not match our records."
    ]
  }
}
````
