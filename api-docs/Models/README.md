# API Data Models

## Overview
This document provides information about the data models used in the Concierge API. These models represent the core entities in the system and their relationships.

## Models

### User
Represents a user in the system.

#### Properties
| Property | Type | Description |
|----------|------|-------------|
| id | integer | Unique identifier for the user |
| name | string | The user's full name |
| email | string | The user's email address |
| role | string | The user's primary role in the system |
| avatar | string | URL to the user's avatar image |
| timezone | string | The user's timezone |
| region | integer | The ID of the user's selected region |

### Region
Represents a geographical region in the system.

#### Properties
| Property | Type | Description                     |
|----------|------|---------------------------------|
| id | integer | Unique identifier for the region |
| name | string | The name of the region          |
| timezone | string | The timezone of the region      |
| currency | string | Currency 3 digits code          |
| currency_symbol | string | Currency Symbol                 |

### Venue
Represents a restaurant or other venue in the system.

#### Properties
| Property           | Type | Description                                      |
|--------------------|------|--------------------------------------------------|
| id                 | integer | Unique identifier for the venue                  |
| name               | string | The name of the venue                            |
| slug               | string | A slug of the venue name                         |
| city               | string | The city where the venue is located              |
| state              | string | The state where the venue is located             |
| zip                | string | The ZIP code of the venue                        |
| phone              | string | The contact phone number for the venue           |
| email              | string | The contact email for the venue                  |
| profile_photo_path | string | The URL of the venue's image                     |
| region             | integer | The ID of the region where the venue is located  |
| status             | enum | The status of the venue (ACTIVE, INACTIVE, etc.) |

### Booking
Represents a reservation at a venue.

#### Properties
| Property                | Type | Description                                                     |
|-------------------------|------|-----------------------------------------------------------------|
| id                      | integer | Unique identifier for the booking                               |
| schedule_template_id    | integer | The ID of the Schedule Template for the booking                 |
| booking_at              | datetime | The date and time of the booking                                |
| guest_count             | integer | The number of guests for the booking                            |
| guest_first_name        | string | The first name of the guest                                     |
| guest_last_name         | string | The last name of the guest                                      |
| guest_phone             | string | The phone number of the guest                                   |
| guest_email             | string | The email address of the guest                                  |
| notes                   | string | Additional notes for the booking                                |
| status                  | enum | The status of the booking (PENDING, CONFIRMED, CANCELLED, etc.) |
| is_prime                | boolean | Whether the booking is a prime booking                          |
| concierge_referral_type | string | The type of referral (app, web, etc.)                           |

### ScheduleTemplate
Represents a template for a venue's schedule.

#### Properties
| Property         | Type    | Description                                   |
|------------------|---------|-----------------------------------------------|
| id               | integer | Unique identifier for the schedule template   |
| venue_id         | integer | The ID of the venue for the schedule template |
| day_of_week      | string  | The day of the week (monday, friday, sunday)  |
| start_time       | time    | The start time for the schedule template      |
| end_time         | time    | The end time for the schedule template        |
| is_available     | integer | The total capacity for this timeslot          |
| available_tables | integer | The amount of table available to book        |

### RoleProfile
Represents a user's role profile.

#### Properties
| Property | Type | Description |
|----------|------|-------------|
| id | integer | Unique identifier for the role profile |
| user_id | integer | The ID of the user who owns the profile |
| role_id | integer | The ID of the role associated with the profile |
| name | string | The name of the role profile |
| is_active | boolean | Whether the profile is currently active |

## Relationships

- **User** has many **RoleProfiles**
- **User** belongs to a **Region**
- **Region** has many **Venues**
- **Venue** belongs to a **Region**
- **Venue** has many **ScheduleTemplates**
- **Booking** belongs to a **ScheduleTemplate**

## Enums

### BookingStatus
- PENDING
- CONFIRMED
- CANCELLED
- COMPLETED
- ABANDONED
- GUEST_ON_PAGE
- NO_SHOW
- REFUNDED
- PARTIALLY_REFUNDED

### VenueStatus
- ACTIVE
- PENDING
- UPCOMING
- HIDDEN
- DRAFT
- SUSPENDED
