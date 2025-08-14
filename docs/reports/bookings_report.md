# Bookings Report Command

Generates a CSV of recent confirmed bookings with Gross Revenue.

## Command

```bash
php artisan report:bookings {limit} {region?} {--output=}
```

- limit: number of bookings (newest first by created_at)
- region: optional region id or name (e.g., `miami` or `Miami`). If omitted, includes all regions.
- --output: optional absolute path to write CSV. Defaults to `storage/app/reports/bookings-{region-or-all}-{timestamp}.csv`

## Rules

- Statuses: confirmed only (`confirmed`, `venue_confirmed`).
- Timezone: all timestamps formatted in America/New_York.

## Columns

- Booking ID
- Created
- Booking Date
- Guest name
- Guest email
- Guest phone (E164 format)
- Guest count
- Venue
- Region
- Concierge
- Hotel/Company
- Status
- Prime Status
- Gross Revenue (per booking)
- Currency
