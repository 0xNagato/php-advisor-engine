# VIP Session and User Token Commands

This document explains how to use the command-line tools for creating VIP session tokens and user tokens for testing and administrative purposes.

## VIP Session Token Command

### Command: `vip:create-session-token`

Creates a VIP session token for a specific VIP code or demo mode. Now includes search and list functionality.

#### Basic Usage

```bash
# Create a VIP session token for a specific VIP code
php artisan vip:create-session-token MIAMI2024

# Create a demo session token
php artisan vip:create-session-token DEMO --demo

# Create a token with custom expiration (48 hours)
php artisan vip:create-session-token MIAMI2024 --expires=48
```

#### Search and List Options

```bash
# Search for VIP codes by concierge name, hotel, or email
php artisan vip:create-session-token SEARCH --search="John Doe"

# Search by hotel name
php artisan vip:create-session-token SEARCH --search="Luxury Resort"

# Search by email
php artisan vip:create-session-token SEARCH --search="john@example.com"

# List all VIP codes with details
php artisan vip:create-session-token LIST --list
```

#### Parameters

- `vip_code` (required): The VIP code to create a session for (or "SEARCH"/"LIST" for search/list modes)
- `--demo`: Create a demo session instead of using a VIP code
- `--expires=24`: Hours until the token expires (default: 24)
- `--search=`: Search VIP codes by concierge name, hotel, or email
- `--list`: List all VIP codes with details

#### Search Examples

```bash
# Find VIP codes for a specific concierge
php artisan vip:create-session-token SEARCH --search="John Doe"

# Find VIP codes for a specific hotel
php artisan vip:create-session-token SEARCH --search="Miami Resort"

# Find VIP codes by email
php artisan vip:create-session-token SEARCH --search="john@hotel.com"

# List all VIP codes
php artisan vip:create-session-token LIST --list
```

#### Search Output Example

```
Searching VIP codes for: 'John Doe'

Found 2 VIP code(s):
+----------+------------+------------------+------------------+--------+----------+
| Code     | Concierge  | Hotel            | Email            | Active | Created  |
+----------+------------+------------------+------------------+--------+----------+
| MIAMI2024 | John Doe   | Luxury Resort    | john@hotel.com   | Yes    | Jun 15   |
| MIAMI2025 | John Doe   | Beach Resort     | john@hotel.com   | Yes    | Jun 16   |
+----------+------------+------------------+------------------+--------+----------+

To create a session token, use:
  php artisan vip:create-session-token CODE_NAME
```

#### List Output Example

```
All VIP Codes in Database:

+----------+------------+------------------+------------------+--------+----------+
| Code     | Concierge  | Hotel            | Email            | Active | Created  |
+----------+------------+------------------+------------------+--------+----------+
| MIAMI2024 | John Doe   | Luxury Resort    | john@hotel.com   | Yes    | Jun 15   |
| MIAMI2025 | John Doe   | Beach Resort     | john@hotel.com   | Yes    | Jun 16   |
| NYC2024   | Jane Smith | Manhattan Hotel  | jane@hotel.com   | Yes    | Jun 10   |
| LA2024    | Bob Wilson | Sunset Hotel     | bob@hotel.com    | No     | Jun 5    |
+----------+------------+------------------+------------------+--------+----------+

To create a session token, use:
  php artisan vip:create-session-token CODE_NAME

To search for specific codes, use:
  php artisan vip:create-session-token SEARCH --search="term"
```

#### Token Creation Examples

```bash
# Create VIP session for existing code
php artisan vip:create-session-token MIAMI2024

# Create demo session for testing
php artisan vip:create-session-token DEMO --demo

# Create token that expires in 12 hours
php artisan vip:create-session-token MIAMI2024 --expires=12
```

#### Token Creation Output Example

```
Creating VIP session token...
VIP Code: MIAMI2024
Demo Mode: No
Expires in: 24 hours
Creating VIP session...

✅ VIP Session Token Created Successfully!

Session Token:
1234|AbCdEfGhIjKlMnOpQrStUvWxYz

Expires At:
2024-06-20T17:05:10.000Z

Mode: VIP Session
VIP Code: MIAMI2024
Concierge: John Doe
Hotel: Luxury Resort Miami

Usage Examples:
  curl -H "Authorization: Bearer 1234|AbCdEfGhIjKlMnOpQrStUvWxYz" https://api.example.com/api/me
  curl -H "Authorization: Bearer 1234|AbCdEfGhIjKlMnOpQrStUvWxYz" https://api.example.com/api/venues

⚠️  This token will expire in 24 hours and should only be used for testing.
```

## User Token Command

### Command: `user:create-token`

Creates a Sanctum token for any user in the system.

#### Basic Usage

```bash
# Create a token for a user by ID
php artisan user:create-token 123

# Create a token for a user by email
php artisan user:create-token john@example.com

# Create a token with custom name and expiration
php artisan user:create-token john@example.com --name="test-token" --expires=48
```

#### Parameters

- `user` (required): The user ID or email to create a token for
- `--name=`: Token name (default: command-line-token)
- `--expires=24`: Hours until the token expires (default: 24)
- `--abilities=*`: Token abilities (default: ["*"])

#### Examples

```bash
# Create token for user by ID
php artisan user:create-token 123

# Create token for user by email
php artisan user:create-token admin@primavip.co

# Create token with custom name
php artisan user:create-token 123 --name="api-testing"

# Create token with limited abilities
php artisan user:create-token 123 --abilities="read" --abilities="write"
```

#### Output Example

```
Creating user token...
User: john@example.com
Token Name: command-line-token
Expires in: 24 hours
Abilities: *
Creating token...

✅ User Token Created Successfully!

Token:
5678|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ

User Information:
ID: 123
Name: John Doe
Email: john@example.com

Token Details:
Name: command-line-token
Expires At: 2024-06-20T17:05:10.000Z
Abilities: *

Usage Examples:
  curl -H "Authorization: Bearer 5678|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ" https://api.example.com/api/me
  curl -H "Authorization: Bearer 5678|XyZaBcDeFgHiJkLmNoPqRsTuVwXyZ" https://api.example.com/api/venues

⚠️  This token will expire in 24 hours and should only be used for testing.
```

## Setup Requirements

### Demo User Setup

Before using the VIP session command with demo mode, ensure the demo user exists:

```bash
php artisan vip:setup-demo-user
```

This creates a demo user and concierge for fallback sessions.

### Finding VIP Codes

To find VIP codes in the system, you have several options:

```bash
# List all VIP codes
php artisan vip:create-session-token LIST --list

# Search by concierge name
php artisan vip:create-session-token SEARCH --search="John Doe"

# Search by hotel name
php artisan vip:create-session-token SEARCH --search="Luxury Resort"

# Search by email
php artisan vip:create-session-token SEARCH --search="john@hotel.com"
```

## Security Notes

1. **Token Expiration**: All tokens expire after 24 hours by default
2. **Testing Only**: These tokens should only be used for testing and development
3. **Token Storage**: Tokens are stored securely using SHA-256 hashing
4. **Demo Mode**: Demo sessions provide limited functionality for invalid VIP codes

## API Usage

Once you have a token, you can use it with the API:

```bash
# Test the token
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.example.com/api/me

# Get venues
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.example.com/api/venues

# Get user profile
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.example.com/api/profile
```

## Troubleshooting

### Common Issues

1. **"VIP code not found"**: The VIP code doesn't exist in the database
   - Use `--search` to find available codes
   - Use `--list` to see all codes
2. **"Demo user not found"**: Run `php artisan vip:setup-demo-user` first
3. **"User not found"**: Check the user ID or email is correct
4. **Token validation fails**: The token may have expired or been revoked

### Search Tips

- Search is case-insensitive and uses partial matching
- You can search by:
  - Concierge name (e.g., "John Doe")
  - Hotel name (e.g., "Luxury Resort")
  - Email address (e.g., "<john@hotel.com>")
  - VIP code (e.g., "MIAMI")
- Use `--list` to see all codes if search doesn't find what you're looking for

### Debugging

Use the `--verbose` flag for more detailed output:

```bash
php artisan vip:create-session-token MIAMI2024 --verbose
```
