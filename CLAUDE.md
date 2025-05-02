# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build/Test/Lint Commands

- **Development server**: This project is run via Laravel Herd it's always available
- **Frontend dev**: I always have assets compiled on file change, no need to worry about this.
- **Build frontend**: `npm run build`
- **Run tests**: `./vendor/bin/pest`
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