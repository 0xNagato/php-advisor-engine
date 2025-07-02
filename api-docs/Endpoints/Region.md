# Region Endpoint

## Overview
This endpoint provides functionality for retrieving a list of active regions and updating the user's selected region.

## Get Regions

### Request
- **Method:** GET
- **URL:** `/api/regions`
- **Authentication:** Not required (public endpoint)

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Accept | application/json | Yes | Specifies the expected response format |

#### Parameters
No parameters required.

#### Example Request
```bash
curl -X GET \
  https://api.example.com/api/regions \  
  -H 'Accept: application/json'
```

### Response

#### Success Response
- **Status Code:** 200 OK

##### Response Body
```json
{
    "data": {
        "miami": "Miami",
        "ibiza": "Ibiza",
        "los_angeles": "Los Angeles"
    }
}
```

The response is a key-value object where the keys are region IDs and the values are region names.

## Update User's Region

### Request
- **Method:** POST
- **URL:** `/api/regions`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |
| Content-Type | application/json | Yes | Specifies the request format |

#### Request Body
| Parameter | Type   | Required | Description |
|-----------|--------|----------|-------------|
| region | string | Yes | The ID of the region to set as the user's selected region |

#### Example Request
```bash
curl -X POST \
  https://api.example.com/api/regions \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "region": 1
  }'
```

### Response

#### Success Response
- **Status Code:** 204 No Content

No response body is returned for a successful request.

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
  "region": [
    "The region field is required."
  ]
}
```

or

```json
{
  "region": [
    "The selected region is invalid."
  ]
}
```

## Notes
- The GET endpoint returns only active regions
- The POST endpoint updates the user's selected region in their profile
- After updating the region, the user's experience in the application will be tailored to that region (e.g., venues, timeslots, etc.)
