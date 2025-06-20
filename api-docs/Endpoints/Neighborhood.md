# Neighborhood Endpoint

## Overview
This endpoint provides functionality for retrieving a list of neighborhoods, with optional filtering by region.

## Get Neighborhoods

### Request
- **Method:** GET
- **URL:** `/api/neighborhoods`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| region | string | No | Filter neighborhoods by region ID |

#### Example Request
```bash
curl -X GET \
  https://api.example.com/api/neighborhoods \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Example Request with Region Filter
```bash
curl -X GET \
  https://api.example.com/api/neighborhoods?region=miami \
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
        "brickell": "Brickell",
        "south_beach": "South Beach",
        "wynwood": "Wynwood",
        "coconut_grove": "Coconut Grove"
    }
}
```

The response is a key-value object where the keys are neighborhood IDs and the values are neighborhood names.

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Notes
- The endpoint returns all neighborhoods if no region filter is provided
- When a region filter is provided, only neighborhoods in that region are returned
- Each neighborhood belongs to a specific region
