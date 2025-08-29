# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Available MCP (Model Context Protocol) Tools

This project has access to several MCP servers providing specialized tools:

### Laravel Boost MCP (`mcp__laravel-boost__*`)
Comprehensive Laravel development tools for configuration, database operations, debugging, and documentation. See the "Laravel Boost" section for detailed tool descriptions.

### Laravel Herd MCP (`mcp__herd__*`)
Tools for managing PHP versions and Laravel Herd site configurations. See the "Laravel Herd" section for detailed tool descriptions.

### PostgreSQL MCP (`mcp__postgres__query`)
Direct read-only SQL query access to the PostgreSQL database.

### General MCP Tools
- **`ListMcpResourcesTool`** - List available resources from configured MCP servers
- **`ReadMcpResourceTool`** - Read specific resources from MCP servers

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
- **CRITICAL: Do not commit changes until after the user has reviewed them** - Always wait for explicit approval before creating commits
- **CRITICAL: Always run tests after making changes** - Use `./vendor/bin/pest --parallel` to verify changes work
- **CRITICAL: Double-check which test is failing before deleting** - Match line numbers exactly to error output
- Use the PostgreSQL MCP (`mcp__postgres__query`) for database lookups when available
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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.11
- filament/filament (FILAMENT) - v3
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v3
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v3
- rector/rector (RECTOR) - v2
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

### Available Laravel Boost Tools

#### Configuration & Information Tools
- **`list-available-config-keys`** - List all available Laravel configuration keys (from config/*.php) in dot notation
- **`get-config`** - Get the value of a specific config variable using dot notation (e.g., "app.name", "database.default")
- **`list-available-env-vars`** - List all available environment variable names from a given .env file
- **`application-info`** - Get comprehensive application information including PHP version, Laravel version, database engine, all installed packages with their versions, and all Eloquent models. Use this on each new chat to understand the project context

#### Database Tools
- **`database-schema`** - Read the database schema including table names, columns, data types, indexes, foreign keys
- **`database-query`** - Execute read-only SQL queries against the configured database
- **`database-connections`** - List the configured database connection names
- **`tinker`** - Execute PHP code in the Laravel application context (like artisan tinker). Use for debugging, checking if functions exist, and testing code snippets

#### Debugging & Error Tools
- **`last-error`** - Get details of the last error/exception created in this application on the backend
- **`browser-logs`** - Read the last N log entries from the BROWSER log for debugging frontend/JS issues
- **`read-log-entries`** - Read the last N log entries from the application log, correctly handling multi-line PSR-3 formatted logs

#### Documentation & Help Tools
- **`search-docs`** - Search for up-to-date version-specific Laravel ecosystem documentation. Perfect for Laravel, Inertia, Pest, Livewire, Filament, Nova, Tailwind, etc. Always use this before other documentation approaches
- **`report-feedback`** - Report user feedback about Boost or Laravel experience (only for Boost/Laravel ecosystem feedback)

#### Artisan & Routes
- **`list-artisan-commands`** - List all available Artisan commands registered in this application
- **`list-routes`** - List all available routes defined in the application, including Folio routes if used

#### URLs
- **`get-absolute-url`** - Get the absolute URL for a given relative path or named route. Always use this when sharing URLs with the user

### Best Practices for Laravel Boost Tools
- Use `application-info` at the start of each chat to understand the project context
- Prefer `tinker` for debugging and testing code snippets over creating temporary files
- Use `database-query` for read-only database operations instead of `tinker` when possible
- Always use `search-docs` before implementing Laravel ecosystem features to ensure you follow best practices
- Use `get-absolute-url` whenever you need to share a URL with the user
- Check `last-error` and `browser-logs` when debugging issues

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries, package information is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.

### Available Laravel Herd MCP Tools

#### PHP Management
- **`mcp__herd__get_all_php_versions`** - Get a list of all PHP versions and their status (installed, in-use, etc.)
- **`mcp__herd__install_php_version`** - Install or update a specific PHP version (e.g., "8.3", "8.4")

#### Site Management
- **`mcp__herd__get_all_sites`** - Get a list of all sites managed by Laravel Herd with their configurations
- **`mcp__herd__get_site_information`** - Get information about the current project including URL, path, secure status, PHP version, etc.
- **`mcp__herd__secure_or_unsecure_site`** - Enable or disable HTTPS for the current site
- **`mcp__herd__isolate_or_unisolate_site`** - Isolate site to use specific PHP version or use global PHP version

### Laravel Herd Best Practices
- Use `mcp__herd__get_site_information` to understand the current project's Herd configuration
- Check available PHP versions before attempting to isolate a site to a specific version
- The site URL is automatically determined by Herd based on the directory name


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.
- **CRITICAL: Always follow "The Filament Way"** - Before implementing any Filament-related functionality, use the `search-docs` tool to verify you're following proper Filament patterns and conventions. If existing code doesn't follow Filament best practices, confirm with the user whether you should refactor it to use proper Filament approaches.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== filament/v3 rules ===

## Filament 3

## Version 3 Changes To Focus On
- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource's directory, i.e. `app/Filament/Resources/PostResource/Pages/`.
- Forms use the `Forms\Components` namespace for form fields.
- Tables use the `Tables\Columns` namespace for table columns.
- New RichEditor component available (`Filament\Forms\Components\RichEditor`).
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the Laravel 11 structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>