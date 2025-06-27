# Contact Form Endpoint

## Overview
This endpoint allows authenticated users to submit a contact form message, which will be sent via email to the system administrators.

## Submit Contact Form

### Request
- **Method:** POST
- **URL:** `/api/contact`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |
| Content-Type | application/json | Yes | Specifies the request format |

#### Request Body
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| message | string | Yes | The message to send (max: 500 characters) |

#### Example Request
```bash
curl -X POST \
  https://api.example.com/api/contact \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "message": "I have a question about my booking. Can you please help me?"
  }'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
  "message": "Message sent successfully"
}
```

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

##### 422 Unprocessable Entity
```json
{
  "message": [
    "The message field is required."
  ]
}
```

or

```json
{
  "message": [
    "The message may not be greater than 500 characters."
  ]
}
```

## Notes
- The contact form message is sent via email to the system administrators
- The user's information (name, email, etc.) is automatically included in the email
- This endpoint is only available to authenticated users
