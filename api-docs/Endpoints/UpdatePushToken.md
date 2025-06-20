# Update Push Token Endpoint

## Overview
This endpoint allows users to update their push notification token for mobile devices. The token is used to send push notifications to the user's device.

## Request
- **Method:** POST
- **URL:** `/api/update-push-token`
- **Authentication:** Required

### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |
| Content-Type | application/json | Yes | Specifies the request format |

### Request Body
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| push_token | string | Yes | The push notification token for the user's device |

### Example Request
```bash
curl -X POST \
  https://api.example.com/api/update-push-token \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "push_token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
  }'
```

## Response

### Success Response
- **Status Code:** 200 OK

#### Response Body
```json
{
  "message": "Push token updated successfully"
}
```

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
  "push_token": [
    "The push token field is required."
  ]
}
```

## Notes
- This endpoint is used by mobile applications to register for push notifications
- The push token is specific to the user's device and is used to send notifications to that device
- The token is stored in the user's record in the database as `expo_push_token`
- The token format depends on the push notification service being used (e.g., Expo, Firebase)
