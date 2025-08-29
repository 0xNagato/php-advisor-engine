# Availability Calendar Endpoint

## Overview

This endpoint provides information about available venues and timeslots for a specific date, guest count, and reservation time. It's used to display the availability calendar in the booking interface.

## Request

-   **Method:** GET
-   **URL:** `/api/calendar`
-   **Authentication:** Required

### Headers

| Header        | Value            | Required | Description                                |
| ------------- | ---------------- | -------- | ------------------------------------------ |
| Authorization | Bearer {token}   | Yes      | VIP session token from `/api/vip/sessions` |
| Accept        | application/json | Yes      | Specifies the expected response format     |

### Parameters

| Parameter        | Type                | Required | Description                                                                                                                                                                                                                                                                                                                         |
| ---------------- | ------------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| date             | string (YYYY-MM-DD) | Yes      | The date for which to check availability                                                                                                                                                                                                                                                                                            |
| guest_count      | integer             | Yes      | The number of guests for the reservation (minimum: 1)                                                                                                                                                                                                                                                                               |
| reservation_time | string (HH:MM:SS)   | Yes      | The time for the reservation. If the reservation is for the current day, the time must be at least 30 minutes from the current time.                                                                                                                                                                                                |
| timeslot_count   | integer             | No       | The number of timeslots to return (default: 5, min: 1, max: 30). For "All Times" feature, set to 30 with reservation_time=08:00:00 to get all available slots from 8 AM to 11 PM                                                                                                                                                    |
| time_slot_offset | integer             | No       | The offset for timeslots (default: 1)                                                                                                                                                                                                                                                                                               |
| cuisine          | array               | No       | Filter venues by cuisine types                                                                                                                                                                                                                                                                                                      |
| neighborhood     | string              | No       | Filter venues by neighborhood                                                                                                                                                                                                                                                                                                       |
| specialty        | array               | No       | Filter venues by specialty types                                                                                                                                                                                                                                                                                                    |
| region           | string              | No       | Region ID to override the concierge's default region and view availability in a different region. When specified, the response will show venues and timeslots for the specified region with timezone adjustments. Valid values: `miami`, `ibiza`, `mykonos`, `paris`, `london`, `st_tropez`, `new_york`, `los_angeles`, `las_vegas` |
| user_latitude    | float               | No       | User's current latitude for distance calculations (range: -90 to 90)                                                                                                                                                                                                                                                                 |
| user_longitude   | float               | No       | User's current longitude for distance calculations (range: -180 to 180)                                                                                                                                                                                                                                                              |

### Example Requests

#### Basic Request (uses concierge's default region)

```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Request with Region Override

```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00&region=miami' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Request with All Filters and Region

```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00&timeslot_count=5&cuisine[]=italian&cuisine[]=japanese&neighborhood=downtown&specialty[]=steak&region=paris' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### "All Times" Request (Show All Available Times for the Day)

```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=08:00:00&timeslot_count=30&time_slot_offset=0' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

#### Request with User Location

```bash
curl -X GET \
  'https://api.example.com/api/calendar?date=2023-06-15&guest_count=4&reservation_time=19:00:00&user_latitude=40.7580&user_longitude=-73.9855' \
  -H 'Authorization: Bearer your-api-token' \
  -H 'Accept: application/json'
```

## Response

### Success Response

-   **Status Code:** 200 OK

#### Response Body

```json
{
    "data": {
        "venues": [
            {
                "id": 76,
                "name": "Call Me Gaby",
                "slug": "miami-call-me-gaby",
                "address": "1424 20th Street, Miami Beach, FL 33139",
                "description": "Mediterranean restaurant featuring fresh seafood and vibrant atmosphere",
                "images": [
                    "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/call_me_gaby_1.jpg"
                ],
                "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/call_me_gaby.png",
                "cuisines": [
                    {"id": "mediterranean", "name": "Mediterranean"},
                    {"id": "seafood", "name": "Seafood"}
                ],
                "specialty": [
                    {"id": "seafood", "name": "Fresh Fish"},
                    {"id": "wine_bar", "name": "Wine Bar"}
                ],
                "neighborhood": "South Beach",
                "region": "miami",
                "status": "active",
                "formatted_location": "South Beach, Miami",
                "non_prime_time": null,
                "business_hours": null,
                "tier": null,
                "tier_label": "Standard",
                "rating": 4.6,
                "price_level": 3,
                "price_level_display": "$$$",
                "rating_display": "4.6/5",
                "review_count": 459,
                "schedules": [
                    {
                        "id": 102882,
                        "schedule_template_id": 102882,
                        "is_bookable": false,
                        "prime_time": true,
                        "time": {
                            "value": "4:00 PM",
                            "raw": "16:00:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$100",
                        "has_low_inventory": false,
                        "is_available": true,
                        "remaining_tables": 0
                    },
                    {
                        "id": 102887,
                        "schedule_template_id": 102887,
                        "is_bookable": false,
                        "prime_time": true,
                        "time": {
                            "value": "4:30 PM",
                            "raw": "16:30:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$100",
                        "has_low_inventory": false,
                        "is_available": true,
                        "remaining_tables": 0
                    },
                    {
                        "id": 102892,
                        "schedule_template_id": 102892,
                        "is_bookable": true,
                        "prime_time": true,
                        "time": {
                            "value": "5:00 PM",
                            "raw": "17:00:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$100",
                        "has_low_inventory": true,
                        "is_available": true,
                        "remaining_tables": 3
                    }
                ]
            },
            {
                "id": 63,
                "name": "Mandolin",
                "slug": "miami-mandolin",
                "address": "4312 NE 2nd Ave, Miami, FL 33137",
                "description": "Aegean-inspired restaurant with authentic Greek cuisine",
                "images": [
                    "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/mandolin_1.jpg"
                ],
                "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/mandolin.png",
                "cuisines": [
                    {"id": "greek", "name": "Greek"},
                    {"id": "mediterranean", "name": "Mediterranean"}
                ],
                "specialty": [
                    {"id": "seafood", "name": "Seafood"},
                    {"id": "outdoor_seating", "name": "Outdoor Seating"}
                ],
                "neighborhood": "Design District",
                "region": "miami",
                "status": "active",
                "formatted_location": "Design District, Miami",
                "non_prime_time": null,
                "business_hours": null,
                "tier": null,
                "tier_label": "Standard",
                "rating": 4.6,
                "price_level": 3,
                "price_level_display": "$$$",
                "rating_display": "4.6/5",
                "review_count": 459,
                "schedules": [
                    {
                        "id": 81042,
                        "schedule_template_id": 81042,
                        "is_bookable": true,
                        "prime_time": true,
                        "time": {
                            "value": "4:00 PM",
                            "raw": "16:00:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$200",
                        "has_low_inventory": true,
                        "is_available": true,
                        "remaining_tables": 2
                    },
                    {
                        "id": 81047,
                        "schedule_template_id": 81047,
                        "is_bookable": true,
                        "prime_time": true,
                        "time": {
                            "value": "4:30 PM",
                            "raw": "16:30:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$200",
                        "has_low_inventory": true,
                        "is_available": true,
                        "remaining_tables": 1
                    }
                ]
            }
        ],
        "timeslots": ["4:00 PM", "4:30 PM", "5:00 PM", "5:30 PM", "6:00 PM"]
    }
}
```

#### Response with Venue Collection (VIP Session)

```json
{
    "data": {
        "venues": [
            {
                "id": 76,
                "name": "Call Me Gaby",
                "slug": "miami-call-me-gaby",
                "address": "1424 20th Street, Miami Beach, FL 33139",
                "description": "Mediterranean restaurant featuring fresh seafood and vibrant atmosphere",
                "images": [
                    "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/call_me_gaby_1.jpg"
                ],
                "logo": "https://prima-bucket.nyc3.digitaloceanspaces.com/venues/call_me_gaby.png",
                "cuisines": [
                    {"id": "mediterranean", "name": "Mediterranean"},
                    {"id": "seafood", "name": "Seafood"}
                ],
                "specialty": [
                    {"id": "seafood", "name": "Fresh Fish"},
                    {"id": "wine_bar", "name": "Wine Bar"}
                ],
                "neighborhood": "South Beach",
                "region": "miami",
                "status": "active",
                "formatted_location": "South Beach, Miami",
                "non_prime_time": null,
                "business_hours": null,
                "tier": null,
                "tier_label": "Standard",
                "rating": 4.5,
                "price_level": 3,
                "price_level_display": "$$$",
                "rating_display": "4.5/5",
                "review_count": 234,
                "collection_note": "Amazing food and great atmosphere!",
                "schedules": [
                    {
                        "id": 102882,
                        "schedule_template_id": 102882,
                        "is_bookable": true,
                        "prime_time": true,
                        "time": {
                            "value": "7:00 PM",
                            "raw": "19:00:00"
                        },
                        "date": "2025-06-17",
                        "fee": "$100",
                        "has_low_inventory": false,
                        "is_available": true,
                        "remaining_tables": 5
                    }
                ]
            }
        ],
        "timeslots": ["7:00 PM", "7:30 PM", "8:00 PM", "8:30 PM", "9:00 PM"],
        "venue_collection": {
            "id": 1,
            "name": "Miami Favorites",
            "description": "Curated selection of the best venues in Miami",
            "is_active": true,
            "source": "vip_code",
            "items_count": 5
        }
    }
}
```

#### Response Fields

| Field                                          | Type          | Description                                                                                                         |
| ---------------------------------------------- | ------------- | ------------------------------------------------------------------------------------------------------------------- |
| data.venues                                    | array         | List of venues with their availability                                                                              |
| data.venues[].id                               | integer       | Unique identifier for the venue                                                                                     |
| data.venues[].name                             | string        | Name of the venue                                                                                                   |
| data.venues[].slug                             | string        | URL-friendly slug for the venue                                                                                     |
| data.venues[].address                          | string        | Full address of the venue                                                                                           |
| data.venues[].description                      | string        | Description of the venue and its offerings                                                                          |
| data.venues[].images                           | array         | Array of venue image URLs                                                                                           |
| data.venues[].logo                             | string        | URL of the venue's logo                                                                                             |
| data.venues[].cuisines                         | array         | Array of cuisine objects with id and name (e.g., [{id: "mediterranean", name: "Mediterranean"}])    |
| data.venues[].specialty                        | array         | Array of specialty objects with id and name (e.g., [{id: "fine_dining", name: "Fine Dining"}])     |
| data.venues[].neighborhood                     | string        | Neighborhood where the venue is located                                                                             |
| data.venues[].region                           | string        | Region ID where the venue is located                                                                                |
| data.venues[].status                           | string        | Status of the venue (active, pending, etc.)                                                                         |
| data.venues[].formatted_location               | string        | Human-readable formatted location (e.g., "South Beach, Miami")                                                      |
| data.venues[].non_prime_time                   | string\|null  | Non-prime time information for the venue                                                                            |
| data.venues[].business_hours                   | string\|null  | Business hours information for the venue                                                                            |
| data.venues[].tier                             | integer\|null | Tier level of the venue                                                                                             |
| data.venues[].tier_label                       | string        | Human-readable label for the venue's tier                                                                           |
| data.venues[].rating                           | float\|null   | Google rating (0-5)                                                                                                |
| data.venues[].price_level                      | integer\|null | Price level (1-4) where 1=$, 2=$$, 3=$$$, 4=$$$$                                                                  |
| data.venues[].price_level_display              | string\|null  | Formatted price level (e.g., "$$$")                                                                                |
| data.venues[].rating_display                   | string\|null  | Formatted rating (e.g., "4.5/5")                                                                                   |
| data.venues[].review_count                     | integer\|null | Number of reviews from Google                                                                                       |
| data.venues[].schedules                        | array         | List of schedule slots for the venue                                                                                |
| data.venues[].schedules[].id                   | integer       | Unique identifier for the schedule                                                                                  |
| data.venues[].schedules[].schedule_template_id | integer       | ID of the schedule template needed for booking                                                                      |
| data.venues[].schedules[].is_bookable          | boolean       | Whether the time slot is available for booking                                                                      |
| data.venues[].schedules[].prime_time           | boolean       | Whether the time slot is during prime time                                                                          |
| data.venues[].schedules[].time.value           | string        | Formatted time (e.g., "4:00 PM")                                                                                    |
| data.venues[].schedules[].time.raw             | string        | Raw time format (HH:MM:SS)                                                                                          |
| data.venues[].schedules[].date                 | string        | Date of the schedule (YYYY-MM-DD)                                                                                   |
| data.venues[].schedules[].fee                  | string        | Fee for the reservation at this time slot                                                                           |
| data.venues[].schedules[].has_low_inventory    | boolean       | Whether there is limited availability for this time slot                                                            |
| data.venues[].schedules[].is_available         | boolean       | Whether the venue was open/available for this specific time slot                                                    |
| data.venues[].schedules[].remaining_tables     | integer       | Number of tables still available for booking at this time slot                                                      |
| data.venues[].collection_note                  | string        | Personalized note/review for this venue from the venue collection (only present when venue is part of a collection) |
| data.venues[].approx_minutes                   | integer       | Approximate drive time in minutes from user's location (only present when user coordinates are provided)            |
| data.venues[].distance_miles                   | float         | Distance in miles from user's location (only present when user coordinates are provided)                            |
| data.venues[].distance_km                      | float         | Distance in kilometers from user's location (only present when user coordinates are provided)                       |
| data.timeslots                                 | array         | List of formatted time slots for the requested date                                                                 |
| data.venue_collection                          | object        | Venue collection information (only present when VIP session has an active collection)                               |
| data.venue_collection.id                       | integer       | Unique identifier for the venue collection                                                                          |
| data.venue_collection.name                     | string        | Name of the venue collection                                                                                        |
| data.venue_collection.description              | string        | Description of the venue collection                                                                                 |
| data.venue_collection.is_active                | boolean       | Whether the venue collection is active                                                                              |
| data.venue_collection.source                   | string        | Source of the collection ("vip_code" or "concierge")                                                                |
| data.venue_collection.items_count              | integer       | Number of venues in the collection                                                                                  |

### Error Responses

#### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

#### 422 Unprocessable Entity

```json
{
    "date": ["The date field is required."],
    "guest_count": ["The guest count field is required."],
    "reservation_time": [
        "The reservation time field is required.",
        "The reservation time must be at least 30 minutes from now."
    ],
    "region": ["The selected region is invalid."]
}
```

## Notes

-   The response includes both a list of venues with their availability and a list of time slots
-   The `schedule_template_id` is needed when creating a booking for a specific time slot
-   Time slots are returned in chronological order
-   The number of time slots returned can be controlled with the `timeslot_count` parameter
-   For same-day reservations, the reservation time must be at least 30 minutes from the current time

### Availability Status Logic

The new fields provide detailed information about venue availability:

-   `is_available`: Indicates if the venue was open/operating during this time slot
-   `remaining_tables`: Shows the number of tables still available for booking
-   `is_bookable`: Combines both availability and inventory (venue is open AND has tables available)

**Availability scenarios:**

-   `is_available = true` + `remaining_tables > 0` + `is_bookable = true`: Available for booking
-   `is_available = true` + `remaining_tables = 0` + `is_bookable = false`: **SOLD OUT** (venue open but no tables)
-   `is_available = false` + `remaining_tables = 0` + `is_bookable = false`: **CLOSED** (venue not operating)

### Region Parameter Behavior

-   **Default Behavior**: When no `region` parameter is provided, the API uses the authenticated concierge's default region
-   **Region Override**: When a `region` parameter is specified, the API overrides the concierge's default region and shows availability for the specified region
-   **Timezone Handling**: All date/time calculations (including the 30-minute minimum advance booking rule) are automatically adjusted to use the specified region's timezone
-   **Venue Filtering**: Only venues located in the specified region will be returned in the response
-   **Currency**: Pricing will be displayed in the currency of the specified region (e.g., USD for US regions, EUR for European regions)

### Valid Region Values

| Region ID     | Region Name | Timezone            | Currency |
| ------------- | ----------- | ------------------- | -------- |
| `miami`       | Miami       | America/New_York    | USD      |
| `new_york`    | New York    | America/New_York    | USD      |
| `los_angeles` | Los Angeles | America/Los_Angeles | USD      |
| `las_vegas`   | Las Vegas   | America/Los_Angeles | USD      |
| `ibiza`       | Ibiza       | Europe/Madrid       | EUR      |
| `mykonos`     | Mykonos     | Europe/Athens       | EUR      |
| `paris`       | Paris       | Europe/Paris        | EUR      |
| `london`      | London      | Europe/London       | GBP      |
| `st_tropez`   | St. Tropez  | Europe/Paris        | EUR      |

### Venue Collections

When a VIP session has an active venue collection, the response will include additional data:

-   **Venue Filtering**: Only venues that are part of the active collection will be returned
-   **Collection Ordering**: Venues are returned in the order specified by the collection curator (position-based ordering)
-   **Collection Notes**: Venues may include a `collection_note` field with personalized recommendations
-   **Collection Metadata**: The `venue_collection` object provides information about the active collection
-   **Collection Source**: Collections can be either VIP code-specific or concierge-level (VIP code collections take precedence)

**Collection Priority:**

1. VIP code-level venue collection (if active)
2. Concierge-level venue collection (if active)
3. No collection filtering (all venues in region)

**When venue collections are active:**

-   Venues are filtered to only show those in the collection
-   Venues are ordered by their curator-specified position (not alphabetically or by tier)
-   Each venue may include a personalized note/review
-   The collection metadata helps the frontend understand the curation context
-   This enables concierges and VIP codes to provide curated restaurant experiences

### Distance Calculations

When `user_latitude` and `user_longitude` parameters are provided, the API calculates approximate distances and drive times for each venue:

**Distance Calculation Features:**

-   **Distance Fields**: Each venue will include three additional fields:
    -   `approx_minutes`: Estimated drive time in minutes
    -   `distance_miles`: Distance in miles (rounded to 1 decimal place)
    -   `distance_km`: Distance in kilometers (rounded to 1 decimal place)

-   **Calculation Method**:
    -   Uses the Haversine formula for straight-line distance calculations
    -   Drive time estimates assume a 1.3x road factor (roads aren't straight lines)
    -   Estimates based on 30 mph average urban driving speed

-   **Requirements**:
    -   Both `user_latitude` and `user_longitude` must be provided
    -   Venue must have coordinates stored in the database
    -   Coordinates must be valid (-90 to 90 for latitude, -180 to 180 for longitude)

-   **Use Cases**:
    -   Show "10 min drive" or "5.2 miles away" in venue listings
    -   Sort venues by proximity
    -   Filter venues within a certain distance

**Note**: Venues without stored coordinates will not include distance fields in the response.
