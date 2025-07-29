# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build/Test/Lint Commands

- **Development server**: This project is run via Laravel Herd it's always available
- **Frontend dev**: I always have assets compiled on file change, no need to worry about this.
- **Build frontend**: `npm run build`
- **Run tests**: `./vendor/bin/pest`
- **Run tests in parallel**: `./vendor/bin/pest --parallel` (use this to verify changes work in parallel execution)
- **Run single test**: `./vendor/bin/pest tests/Feature/SomeTest.php`
- **Run specific test method**: `./vendor/bin/pest tests/Feature/SomeTest.php::test_specific_method`
- **Code linting**: `composer run phpcs`
- **Type checking**: `./vendor/bin/phpstan analyse`
- **Code formatting**: `composer run pint` (fixes dirty files) or `composer run pint-test` (dry run)
- **PHP code formatting**: `composer run prettier`

## Code Style Guidelines

- **PHP version**: 8.3+
- **Framework**: Laravel 11 with Filament 3, Livewire 3 and Laravel Actions
- **Type hints**: Always use proper type hints and return types in method signatures
- **Naming**: PascalCase for classes; camelCase for methods and variables; snake_case for DB columns
- **Business Logic**: Place complex business logic in dedicated Action classes (lorisleiva/laravel-actions)
- **Error handling**: Use typed exceptions with clear messages; catch only specific exceptions
- **Imports**: Group imports by type (PHP core, Laravel, third-party, app)
- **Namespaces**: CRITICAL - Always include all required namespaces/imports at the top of files. When using classes like Carbon, Collection, Request, etc., ensure proper `use` statements are added
- **Indentation**: 4 spaces, PSR-2 compliant with Laravel guidelines
- **Models**: Define relationships, casts, and fillable properties; use proper type hints
- **Traits**: Favor composition with traits for reusable functionality
- **PHP Docblocks**: Required for complex methods; include @param and @return tags

## Project-specific Patterns

- Use `Action` classes for encapsulating business logic
- Follow Laravel conventions for Controllers, Models, and Resources
- Use type safety with Data Transfer Objects (DTOs) via spatie/laravel-data
- Implement Filament resources for admin functionality
- Never clear any type of cache locally
- Do not stage files or commit changes until they have been tested and confirmed
- **CRITICAL: Always run tests after making changes** - Use `./vendor/bin/pest --parallel` to verify changes work
- **CRITICAL: Double-check which test is failing before deleting** - Match line numbers exactly to error output
- Use the MySQL MCP whenever database lookups are needed
- **CRITICAL: NEVER add any attribution to git commits** - No Co-Authored-By, no "Generated with Claude Code", no AI attribution of any kind. Commits should appear as if written entirely by the human developer.
- **CRITICAL: NEVER delete production data** - If database appears empty, use `./sync-db.sh --import-only` to restore data, then run `php artisan venue-platforms:update-config` to avoid hitting real customer accounts

## Configuration Notes

### Venue Onboarding Steps
The onboarding process for venues can be configured via the `VENUE_ONBOARDING_STEPS` environment variable or in `config/app.php`. The available steps are:

- `company`: Basic company information
- `venues`: Venue names and regions
- `booking-hours`: Define operating hours
- `prime-hours`: Set prime time hours (when reservations require payment)
- `incentive`: Configure non-prime incentives
- `agreement`: Accept terms and agreement

Default configuration: `company,venues,agreement` (excludes booking-hours, prime-hours, and incentive steps)

When the incentive step is hidden, the system uses the following defaults:
- Non-prime incentives: Enabled (true)
- Per diem amount: $10.00 per guest

### Auto-Approval System
The booking system includes automatic approval for small party bookings that meet specific criteria:

**Auto-Approval Conditions:**
- Party size is 7 guests or under
- Venue has platform integration (Restoo or CoverManager)  
- Successful API response received from the platform
- Platform sync completed successfully

**Behavior:**
- Qualifying bookings are automatically approved (`venue_confirmed_at` set)
- Custom SMS and email notifications sent to venue contacts
- Admin notifications sent for tracking
- Activity logs created for audit trail
- Bookings with 8+ guests continue using manual approval process

**Key Components:**
- `AutoApproveSmallPartyBooking` action handles the approval logic
- `SendAutoApprovalNotificationToVenueContacts` manages notifications
- `VenueContactBookingAutoApproved` notification with custom SMS/email templates
- `BookingPlatformSyncListener` triggers auto-approval after successful platform sync

### CoverManager Availability Sync
The booking system includes automated prime/non-prime management based on restaurant availability:

**Sync Logic:**
- Uses bulk calendar API (`/reserv/availability_calendar_total`) for efficient processing
- Only creates venue time slot overrides when CoverManager availability differs from template defaults
- Processes every 30-minute slot across all party sizes during operating hours
- Respects human overrides and never overwrites manual changes

**Override Patterns:**
- **Template = Non-Prime + NO CM availability** → Override to Prime (customer pays upfront)
- **Template = Prime + HAS CM availability** → Override to Non-Prime (customer pays at restaurant)
- **Template matches CM availability** → No override needed (uses template default)

**Performance:**
- 14.6% override rate (2,707 overrides out of 18,480 possible slots)
- 81% of overrides are Non-Prime → Prime (restaurants lack availability)
- Single API call per venue instead of hundreds of individual calls

**Key Components:**
- `syncCoverManagerAvailability()` method in Venue model handles bulk processing
- `parseCalendarAvailabilityResponse()` processes bulk calendar API responses
- `isHumanCreatedSlot()` protects manual overrides via activity log detection
- Command: `php artisan app:sync-covermanager-availability --venue-id=X --days=7`
