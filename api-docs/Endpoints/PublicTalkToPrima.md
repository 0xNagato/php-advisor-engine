# Public Talk to PRIMA Endpoint

## Overview

Public endpoint to submit the "Talk to PRIMA" form. No authentication required, but the request must originate from a whitelisted domain. The whitelist is configured via `config/forms.php` under `allowed_origins`.

## Submit Form

### Request

- **Method:** POST
- **URL:** `/api/public/talk-to-prima`
- **Authentication:** Not required (Referer must be whitelisted)
- **Rate Limiting:** 5 requests per minute per IP

#### Headers

| Header       | Value                | Required | Description                                                     |
| ------------ | -------------------- | -------- | --------------------------------------------------------------- |
| Referer      | https://your-domain  | Yes      | Must match a whitelisted host (or subdomain via wildcard)       |
| Accept       | application/json     | Yes      | Expected response format                                        |
| Content-Type | application/json     | Yes      | Request content type                                            |

#### Request Body

| Parameter              | Type   | Required | Description                                                                          |
| ---------------------- | ------ | -------- | ------------------------------------------------------------------------------------ |
| role                   | string | Yes      | User role: "Hotel / Property", "Concierge", "Restaurant", "Creator / Influencer", "Other" |
| name                   | string | Yes      | Full name (max 255)                                                                |
| company                | string | No       | Company or property name (max 255)                                                 |
| email                  | string | No       | Email address (email format, max 255)                                              |
| phone                  | string | Yes      | Phone number (max 255)                                                             |
| city                   | string | No       | City (max 255)                                                                     |
| preferred_contact_time | string | No       | Preferred contact time (max 255)                                                   |
| message                | string | No       | Additional message/notes (max 2000)                                                |

#### Example Request

```bash
curl -X POST \
  https://prima.test/api/public/talk-to-prima \
  -H 'Referer: https://www.primavip.co/contact' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
    "role": "Restaurant",
    "name": "Jane Doe",
    "company": "The Grand Hotel",
    "email": "jane@example.com",
    "phone": "+1 555 123 4567",
    "city": "New York",
    "preferred_contact_time": "Morning",
    "message": "I would like to learn more about partnering with PRIMA."
  }'
```

### Responses

#### Success

- **Status Code:** 200 OK

```json
{
  "message": "Message sent successfully"
}
```

#### Validation Error

- **Status Code:** 422 Unprocessable Entity

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "role": ["The role field is required."],
    "name": ["The name field is required."],
    "phone": ["The phone field is required."]
  }
}
```

#### Forbidden (Invalid Referer)

- **Status Code:** 403 Forbidden

```json
{
  "message": "Forbidden"
}
```

#### Rate Limit Exceeded

- **Status Code:** 429 Too Many Requests

```json
{
  "message": "Too Many Attempts."
}
```

## Notes

- Allowed origins are configured in `config/forms.php` under `allowed_origins`. Supports wildcards via `*.example.com`.
- The Referer header must match one of the allowed origins configured in the system.
- Rate limiting is enforced at 5 requests per minute per IP address.
- The email is sent to PRIMA admins; no data is persisted by this endpoint.
- All successful and blocked attempts are logged for security monitoring.


