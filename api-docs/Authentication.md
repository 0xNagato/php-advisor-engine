# Authentication

## Overview
The Concierge API uses Laravel Sanctum for authentication. Most endpoints require authentication with a valid API token.

## Authentication Flow

### 1. Obtaining a Token
To obtain an API token, you need to authenticate with your credentials using the login endpoint:

```
POST /api/login
```

#### Request Body
```json
{
  "email": "your-email@example.com",
  "password": "your-password"
}
```

#### Response
If the authentication is successful, the response will include an API token:

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "role": "user",
            "name": "John Doe",
            "email": "your-email@example.com",
            "avatar": "https://ui-avatars.com/api/?background=312596&color=fff&format=png&name=Demo Concierge",
            "timezone": "Europe/Madrid",
            "region": "ibiza"
            
        },
        "token": "your-api-token"
    }
}
```

### 2. Using the Token
Once you have obtained an API token, you can use it to authenticate your requests by including it in the `Authorization` header:

```
Authorization: Bearer your-api-token
```

### 3. Token Expiration
Tokens do not expire by default, but they can be revoked by the user or by the server. It's recommended to refresh your token periodically to ensure continued access to the API.

### 4. Revoking a Token
To revoke a token, you can use the logout endpoint:

```
POST /api/logout
```

This endpoint requires authentication with the token you want to revoke.

## Example

### Authentication Request
```bash
curl -X POST \
  https://api.example.com/api/login \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "your-email@example.com",
    "password": "your-password"
  }'
```

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
