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
      "name": "Restaurant A",
      "rating": 4.5,
      "price_level": 3,
      "price_level_display": "$$$",
      "rating_display": "4.5/5",
      "review_count": 234
    },
    {
      "id": 2,
      "name": "Restaurant B",
      "rating": 4.2,
      "price_level": 2,
      "price_level_display": "$$",
      "rating_display": "4.2/5",
      "review_count": 156
    },
    {
      "id": 3,
      "name": "Restaurant C",
      "rating": null,
      "price_level": null,
      "price_level_display": null,
      "rating_display": null,
      "review_count": null
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
| data[].rating | float\|null | Google rating (0-5) |
| data[].price_level | integer\|null | Price level (1-4) where 1=$, 2=$$, 3=$$$, 4=$$$$ |
| data[].price_level_display | string\|null | Formatted price level (e.g., "$$$") |
| data[].rating_display | string\|null | Formatted rating (e.g., "4.5/5") |
| data[].review_count | integer\|null | Number of reviews from Google |

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
    "cuisines": [
      {"id": "italian", "name": "Italian"},
      {"id": "mediterranean", "name": "Mediterranean"}
    ],
    "specialty": [
      {"id": "waterfront", "name": "Waterfront"},
      {"id": "fine_dining", "name": "Fine Dining"}
    ],
    "neighborhood": "South Beach",
    "region": "miami",
    "status": "active",
    "formatted_location": "South Beach, Miami",
    "rating": 4.5,
    "price_level": 3,
    "price_level_display": "$$$",
    "rating_display": "4.5/5",
    "review_count": 234,
    "google_place_id": "ChIJ1234567890"
  }
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| data.id | integer | The unique identifier for the venue |
| data.name | string | The name of the venue |
| data.slug | string | URL-friendly version of the venue name |
| data.address | string | Physical address of the venue |
| data.description | string | Detailed description of the venue |
| data.images | array | Array of image paths |
| data.logo | string\|null | Path to venue logo |
| data.cuisines | array | Array of cuisine objects with id and name (e.g., [{id: "italian", name: "Italian"}]) |
| data.specialty | array | Array of specialty objects with id and name (e.g., [{id: "fine_dining", name: "Fine Dining"}]) |
| data.neighborhood | string | Neighborhood identifier |
| data.region | string | Region identifier |
| data.status | string | Venue status (e.g., "active") |
| data.formatted_location | string | Human-readable location |
| data.rating | float\|null | Google rating (0-5) |
| data.price_level | integer\|null | Price level (1-4) where 1=$, 2=$$, 3=$$$, 4=$$$$ |
| data.price_level_display | string\|null | Formatted price level (e.g., "$$$") |
| data.rating_display | string\|null | Formatted rating (e.g., "4.5/5") |
| data.review_count | integer\|null | Number of reviews from Google |
| data.google_place_id | string\|null | Google Places identifier |

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
