# Authentication

## Overview

The Concierge API uses Laravel Sanctum for authentication. Most endpoints require authentication with a valid API token.

## Authentication Flow

### 1. Obtaining a Token

To get an API token, you need to access https://primavip.co/ with your credentials and get the token from your profile
page.

### 2. Using the Token

Once you have obtained an API token, you can use it to authenticate your requests by including it in the `Authorization`
header:

```
Authorization: Bearer your-api-token
```

### 3. Token Expiration

Tokens do not expire by default, but they can be revoked by the user or by the server. It's recommended to refresh your
token periodically to ensure continued access to the API.

### Authenticated Request

```bash
curl -X GET \
  https://api.example.com/api/me \
  -H 'Authorization: Bearer your-api-token'
```

## Error Handling

### 401 Unauthorized

If you try to access a protected endpoint without a valid token, you will receive a 401 Unauthorized response:

```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden

If you try to access a resource that you don't have permission to access, you will receive a 403 Forbidden response:

```json
{
    "message": "You do not have permission to access this resource."
}
```
