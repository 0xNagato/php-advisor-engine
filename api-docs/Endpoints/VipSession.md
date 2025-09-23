# VIP Session Endpoint

## Overview

This endpoint provides functionality for creating and validating VIP session tokens. VIP sessions allow users to access the booking system with a VIP code, providing 24-hour access tokens with fallback to demo mode for invalid codes.

## Create VIP Session

### Request

- **Method:** POST
- **URL:** `/api/vip/sessions`
- **Authentication:** Not required (public endpoint)

#### Headers

| Header       | Value            | Required | Description                            |
| ------------ | ---------------- | -------- | -------------------------------------- |
| Accept       | application/json | Yes      | Specifies the expected response format |
| Content-Type | application/json | Yes      | Specifies the request content type     |

#### Parameters

| Parameter    | Type   | Required | Description                                                            |
| ------------ | ------ | -------- | ---------------------------------------------------------------------- |
| vip_code     | string | Yes      | The VIP code to create a session for (4-12 characters)                |
| query_params | object | No       | Optional query parameters from the VIP landing page (e.g., utm_source, utm_campaign) |

#### Example Request

```bash
curl -X POST \
  https://api.example.com/api/vip/sessions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "vip_code": "MIAMI2024",
    "query_params": {
      "utm_source": "instagram",
      "utm_campaign": "summer2024",
      "utm_content": "ad_123",
      "cuisine": ["italian", "french"],
      "guest_count": 4
    }
  }'
```

### Response

#### Success Response (Valid VIP Code)

- **Status Code:** 200 OK

##### Response Body

```json
{
  "success": true,
  "data": {
    "session_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0",
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": false,
    "flow_type": "white_label",
    "vip_code": {
      "id": 123,
      "code": "MIAMI2024",
      "region": "miami",
      "concierge": {
        "id": 456,
        "name": "John Doe",
        "hotel_name": "Luxury Resort Miami",
        "branding": {
          "logo_url": "https://prima-spaces.nyc3.digitaloceanspaces.com/production/concierges/logos/hotel-logo.png",
          "main_color": "#3B82F6",
          "secondary_color": "#1E40AF",
          "gradient_start": "#3B82F6",
          "gradient_end": "#1E40AF",
          "text_color": "#1F2937",
          "influencer_name": "John Doe",
          "influencer_handle": "johndoe",
          "follower_count": "12.5K",
          "social_url": "https://instagram.com/johndoe"
        }
      }
    }
  }
}
```

#### Success Response (Invalid VIP Code - Demo Mode)

- **Status Code:** 200 OK

##### Response Body

```json
{
  "success": true,
  "data": {
    "session_token": "demo_1718898310",
    "expires_at": "2024-06-20T17:05:10.000Z",
    "is_demo": true,
    "flow_type": "standard",
    "demo_message": "You are viewing in demo mode. Some features may be limited."
  }
}
```

#### Response Fields

| Field                                            | Type    | Description                                                                                         |
| ------------------------------------------------ | ------- | --------------------------------------------------------------------------------------------------- |
| success                                          | boolean | Indicates whether the request was successful                                                        |
| data.session_token                               | string  | Session token for authentication (64 chars for valid, demo_timestamp for demo)                      |
| data.expires_at                                  | string  | ISO 8601 timestamp when the session expires (24 hours from creation)                                |
| data.is_demo                                     | boolean | Whether this is a demo session (fallback for invalid VIP codes)                                     |
| data.flow_type                                   | string  | User flow type: "white_label" for branded VIP codes, "standard" for regular/demo sessions           |
| data.demo_message                                | string  | Message explaining demo mode (only present when is_demo is true)                                    |
| data.vip_code                                    | object  | VIP code information (only present when is_demo is false)                                           |
| data.vip_code.id                                 | integer | The unique identifier for the VIP code                                                              |
| data.vip_code.code                               | string  | The VIP code string                                                                                 |
| data.vip_code.concierge                          | object  | Information about the concierge associated with the VIP code                                        |
| data.vip_code.concierge.branding                 | object  | White-labeling configuration for the concierge's booking experience                                 |
| data.vip_code.concierge.branding.logo_url        | string  | Absolute URL to the concierge's logo image on Digital Ocean Spaces                                  |
| data.vip_code.concierge.branding.main_color      | string  | Primary brand color in hex format                                                                   |
| data.vip_code.concierge.branding.secondary_color | string  | Secondary brand color in hex format                                                                 |
| data.vip_code.concierge.branding.gradient_start  | string  | Start color for gradient backgrounds in hex format                                                  |
| data.vip_code.concierge.branding.gradient_end    | string  | End color for gradient backgrounds in hex format                                                    |
| data.vip_code.concierge.branding.text_color      | string  | Standard text color for the booking interface in hex format                                         |
| data.vip_code.concierge.branding.influencer_name | string  | Name of the influencer or curator (optional)                                                        |
| data.vip_code.concierge.branding.influencer_handle | string  | Social media handle without @ symbol (optional)                                                    |
| data.vip_code.concierge.branding.follower_count  | string  | Formatted follower count (e.g., "12.5K", "1M") (optional)                                          |
| data.vip_code.concierge.branding.social_url      | string  | URL to influencer's social media profile (optional)                                                |
| data.vip_code.region                             | string  | Region ID for venue collection (e.g., "miami", "ibiza") - only present when venue collection exists |

#### Error Responses

##### 422 Validation Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "vip_code": ["The vip code field is required."]
  }
}
```

##### 500 Server Error

```json
{
  "message": "Unable to create session"
}
```

## Validate VIP Session

### Request

- **Method:** POST
- **URL:** `/api/vip/sessions/validate`
- **Authentication:** Not required (public endpoint)

#### Headers

| Header       | Value            | Required | Description                            |
| ------------ | ---------------- | -------- | -------------------------------------- |
| Accept       | application/json | Yes      | Specifies the expected response format |
| Content-Type | application/json | Yes      | Specifies the request content type     |

#### Parameters

| Parameter     | Type   | Required | Description                   |
| ------------- | ------ | -------- | ----------------------------- |
| session_token | string | Yes      | The session token to validate |

#### Example Request

```bash
curl -X POST \
  https://api.example.com/api/vip/sessions/validate \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "session_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0"
  }'
```

### Response

#### Success Response (Valid Token)

- **Status Code:** 200 OK

##### Response Body

```json
{
  "success": true,
  "data": {
    "valid": true,
    "is_demo": false,
    "flow_type": "white_label",
    "session": {
      "id": 789,
      "expires_at": "2024-06-20T17:05:10.000Z"
    },
    "vip_code": {
      "id": 123,
      "code": "MIAMI2024",
      "region": "miami",
      "concierge": {
        "id": 456,
        "name": "John Doe",
        "hotel_name": "Luxury Resort Miami",
        "branding": {
          "logo_url": "https://prima-spaces.nyc3.digitaloceanspaces.com/production/concierges/logos/hotel-logo.png",
          "main_color": "#3B82F6",
          "secondary_color": "#1E40AF",
          "gradient_start": "#3B82F6",
          "gradient_end": "#1E40AF",
          "text_color": "#1F2937",
          "influencer_name": "John Doe",
          "influencer_handle": "johndoe",
          "follower_count": "12.5K",
          "social_url": "https://instagram.com/johndoe"
        }
      }
    }
  }
}
```

#### Error Response (Invalid/Expired Token)

- **Status Code:** 401 Unauthorized

##### Response Body

```json
{
  "success": false,
  "data": {
    "valid": false,
    "message": "Invalid or expired session token"
  }
}
```

## VIP Session Analytics

### Request

- **Method:** GET
- **URL:** `/api/vip/sessions/analytics`
- **Authentication:** Required (authenticated users only)

#### Headers

| Header        | Value            | Required | Description                            |
| ------------- | ---------------- | -------- | -------------------------------------- |
| Authorization | Bearer {token}   | Yes      | Authentication token                   |
| Accept        | application/json | Yes      | Specifies the expected response format |

#### Example Request

```bash
curl -X GET \
  https://api.example.com/api/vip/sessions/analytics \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

### Response

#### Success Response

- **Status Code:** 200 OK

##### Response Body

```json
{
  "success": true,
  "data": {
    "total_sessions": 1247,
    "active_sessions": 89,
    "expired_sessions": 1158,
    "session_creation_rate": {
      "last_24h": 23,
      "last_7d": 156,
      "last_30d": 678
    }
  }
}
```

## Notes

### Query Parameter Tracking

- Query parameters from the VIP landing page can be passed when creating a session
- These parameters are stored with the session and linked to any resulting bookings for analytics
- Common tracking parameters include:
  - Marketing attribution: `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`
  - Booking preferences: `cuisine`, `guest_count`, `budget`, `occasion`
- The React app should capture all query parameters from the URL and pass them when creating a session
- Parameters are stored as JSONB in the database for flexible querying and reporting

### Session Token Expiration

- All session tokens expire after 24 hours
- Expired tokens will be automatically cleaned up by a scheduled task
- The React app should handle token refresh by creating a new session when needed

### Demo Mode Fallback

- If an invalid VIP code is provided, the system automatically falls back to demo mode
- Demo sessions are clearly marked with `is_demo: true`
- Demo sessions provide limited functionality but allow users to explore the system

### Analytics Tracking

- All VIP session events are tracked separately from bookings
- Events include: session creation, validation, demo fallbacks, and cleanup
- Analytics data is available through the authenticated analytics endpoint

### Security

- Session tokens are hashed using SHA-256 before storage
- Only partial token hashes are logged for security
- Demo tokens are clearly identifiable and don't provide access to real data

### Region Locking

- The `region` field is only present when the VIP code or concierge has an active venue collection
- When present, the frontend should lock venue selection to this specific region
- The region is determined by checking:
  1. VIP code-level venue collection first (if active)
  2. Concierge-level venue collection as fallback (if active)
- If no venue collection exists or no collection is active, the `region` field will be `null`
- This allows the frontend to provide region-specific venue filtering for curated collections

### Flow Type

The `flow_type` field helps the frontend determine the appropriate user flow immediately upon session creation:

- **`"white_label"`**: VIP code has branding information
  - Frontend should skip Index/Booking pages and go directly to Search at `/`
  - Use the branding information for white-labeled experience
- **`"standard"`**: Regular VIP code without branding or demo session
  - Frontend should use standard flow: Index → Booking → Search
  - No special branding applied

This prevents UI flashing between different pages and ensures a smooth user experience.
