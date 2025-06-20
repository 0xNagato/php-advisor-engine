# Cuisine Endpoint

## Overview
This endpoint provides functionality for retrieving a list of available cuisines.

## Get Cuisines

### Request
- **Method:** GET
- **URL:** `/api/cuisines`
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
  https://api.example.com/api/cuisines \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
    "data": {
        "american": "American",
        "chinese": "Chinese",
        "french": "French",
        "indian": "Indian",
        "italian": "Italian",
        "japanese": "Japanese",
        "mexican": "Mexican",
        "thai": "Thai"
    }
}
```

The response is a key-value object where the keys are cuisine IDs and the values are cuisine names.

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Notes
- This endpoint returns all available cuisines in the system
- Cuisines are used to categorize venues and filter search results
- Each cuisine has a unique ID and a display name
