# Specialty Endpoint

## Overview
This endpoint provides functionality for retrieving a list of specialties, with optional filtering by region.

## Get Specialties

### Request
- **Method:** GET
- **URL:** `/api/specialties`
- **Authentication:** Required

#### Headers
| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| region | string | No | Filter specialties by region ID |

#### Example Request
```bash
curl -X GET \
  https://api.example.com/api/specialties \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Example Request with Region Filter
```bash
curl -X GET \
  https://api.example.com/api/specialties?region=miami \
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
        "waterfront": "Waterfront",
        "sunset_view": "Sunset view",
        "scenic_view": "Scenic view",
        "on_the_beach": "On the Beach",
        "fine_dining": "Fine Dining",
        "romantic_atmosphere": "Romantic Atmosphere"
    }
}
```

The response is a key-value object where the keys are specialty IDs and the values are specialty names.

#### Error Responses

##### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

## Notes
- The endpoint returns all specialties if no region filter is provided
- When a region filter is provided, only specialties that support that region are returned
- Each specialty can support multiple regions (stored as a comma-separated list in the database)
- Specialties are used to categorize venues and filter search results
