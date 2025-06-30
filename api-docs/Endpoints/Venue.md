# Venue Endpoints

## Overview

These endpoints provide venue information in the user's region. For concierge users, the results are filtered to only include venues they are allowed to access.

## 1. List Venues (Minimal Data)

### Request

- **Method:** GET
- **URL:** `/api/venues`
- **Authentication:** Required

### Headers

| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Parameters

No parameters required.

### Example Request

```bash
curl -X GET \
  https://api.example.com/api/venues \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

## Response

### Success Response

- **Status Code:** 200 OK

#### Response Body

```json
{
  "data": [
    {
      "id": 1,
      "name": "Restaurant A"
    },
    {
      "id": 2,
      "name": "Restaurant B"
    },
    {
      "id": 3,
      "name": "Restaurant C"
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| data | array | List of venues |
| data[].id | integer | The unique identifier for the venue |
| data[].name | string | The name of the venue |

### Error Responses

#### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

## 2. Get Single Venue (Detailed Data)

### Request

- **Method:** GET
- **URL:** `/api/venues/{id}`
- **Authentication:** Required

### Headers

| Header | Value | Required | Description |
|--------|-------|----------|-------------|
| Authorization | Bearer {token} | Yes | Authentication token |
| Accept | application/json | Yes | Specifies the expected response format |

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | The unique identifier of the venue |

### Example Request

```bash
curl -X GET \
  https://api.example.com/api/venues/1 \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Success Response

- **Status Code:** 200 OK

#### Response Body

```json
{
  "data": {
    "id": 1,
    "name": "Restaurant A",
    "slug": "restaurant-a",
    "address": "123 Main St, Miami, FL",
    "description": "A beautiful restaurant...",
    "images": ["path/to/image1.jpg", "path/to/image2.jpg"],
    "logo": "path/to/logo.png",
    "cuisines": ["italian", "mediterranean"],
    "specialty": ["waterfront", "fine_dining"],
    "neighborhood": "South Beach",
    "region": "miami",
    "status": "active",
    "formatted_location": "South Beach, Miami"
  }
}
```

### Error Responses

#### 404 Not Found

```json
{
  "message": "Venue not found"
}
```

#### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

## General Notes

- The `/api/venues` endpoint filters venues based on the user's region and concierge permissions
- The `/api/venues/{id}` endpoint returns any venue by ID without filtering - useful for venue details regardless of user context  
- The venues list endpoint only returns active venues and is ordered alphabetically by name
- Use the minimal data endpoint (`/api/venues`) for dropdowns and selectors
- Use the single venue endpoint (`/api/venues/{id}`) for comprehensive venue information
