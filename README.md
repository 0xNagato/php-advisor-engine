<p align="center">
  <img src="https://avatars.githubusercontent.com/u/173698585?s=100&v=4" alt="PRIMA" width="100">
</p>

# PRIMA Platform

## Overview

PRIMA (Platform for Restaurant Intermediation and Management Application) is an innovative solution designed to address revenue loss in the restaurant industry due to third-party reservation trading and no-shows. By facilitating prime-time reservation sales through a trusted concierge network and optimizing non-prime bookings, PRIMA creates a win-win-win situation for restaurants, concierges, and diners.

## Table of Contents

- [PRIMA Platform](#prima-platform)
    - [Overview](#overview)
    - [Table of Contents](#table-of-contents)
    - [Problem Statement](#problem-statement)
    - [Our Solution](#our-solution)
    - [Key Features](#key-features)
    - [How It Works](#how-it-works)
    - [Benefits](#benefits)
    - [Technology Stack](#technology-stack)
    - [Architecture](#architecture)
        - [Action Pattern and Code Reuse](#action-pattern-and-code-reuse)
    - [Core Components](#core-components)
    - [Venue Management](#venue-management)
        - [Key Features of Venue Management](#key-features-of-venue-management)
    - [Referral System](#referral-system)
        - [Partner Referrals](#partner-referrals)
        - [Concierge Referrals](#concierge-referrals)
        - [Referral Calculation Process](#referral-calculation-process)
    - [Getting Started](#getting-started)

## Problem Statement

The restaurant industry faces significant revenue loss due to:

- Third-party reservation trading
- No-shows for prime-time reservations
- Underutilized capacity during non-prime hours

## Our Solution

PRIMA revolutionizes restaurant reservation management by:

- Allowing in-demand restaurants to monetize prime-time reservations
- Increasing bookings for non-prime reservations
- Providing a trusted network of concierges to manage and fulfill reservations

## Key Features

- Dual booking system for prime and non-prime reservations
- Concierge-mediated bookings
- Automated reservation management system integration
- Real-time analytics for restaurants and concierges
- Sophisticated earnings calculation and distribution system
- Partner and referral management
- Comprehensive venue management tools

## How It Works

1. **Reservation Booking**:
    - Concierges book reservations through the PRIMA hub
    - Prime-time slots are available for a fee
    - Non-prime slots are offered free of charge

2. **Restaurant Notification**:
    - Restaurants receive instant notifications for new bookings
    - Reservations can be automatically added to the restaurant's management system

3. **Analytics and Tracking**:
    - Live analytics provided for both restaurants and concierges
    - Track reservations, earnings, and other key metrics in real-time

## Benefits

- **For Restaurants**:
    - Maximize revenue from prime-time slots
    - Increase bookings during non-prime hours
    - Reduce no-shows and last-minute cancellations

- **For Concierges**:
    - Earn extra income by facilitating reservations
    - Access to exclusive prime-time slots at popular restaurants

- **For Diners**:
    - Enjoy favorite restaurants at preferred times
    - Reliable reservation system through trusted concierges

## Technology Stack

- PHP 8.3
- Laravel 11
- FilamentPHP 3
- Livewire
- TailwindCSS
- Laravel Actions (lorisleiva/laravel-actions)
- MySQL Version 8.0 or higher

## Architecture

PRIMA follows a modern, modular architecture leveraging Laravel's robust features and additional packages to ensure clean, maintainable, and reusable code:

- **MVC Pattern**: Utilizes Laravel's Model-View-Controller architecture for clear separation of concerns.
- **Service Layer**: Implements a service layer for complex business logic, particularly for booking calculations and earnings distribution.
- **Action Pattern**: Leverages the Laravel Actions package by Loris Leiva to encapsulate business logic into versatile, reusable classes.
- **Event-Driven**: Utilizes Laravel's event system for decoupled, scalable operations.
- **API-First**: Designed with API endpoints to support mobile applications and potential third-party integrations.
- **Admin Panel**: Uses FilamentPHP for a powerful, customizable admin interface.

### Action Pattern and Code Reuse

PRIMA extensively uses the Laravel Actions package to structure its business logic, promoting code reuse across different parts of the application:

1. **Unified Business Logic**: Actions encapsulate core functionalities, allowing the same logic to be used in multiple contexts without duplication.
2. **Versatile Usage**: A single Action can serve as a controller endpoint, a command line operation, a queued job, or a simple function call, promoting consistency across different interfaces.
3. **Admin Panel Integration**: Actions seamlessly integrate with Filament resources, allowing the same business logic to be used in both the admin panel and other parts of the application.
4. **API Consistency**: The same Actions used in the admin panel can be easily exposed as API endpoints, ensuring consistency between admin operations and API functionality.
5. **Reduced Duplication**: Instead of implementing the same logic in controllers, jobs, and commands separately, Actions allow for a single implementation that can be reused across these contexts.
6. **Simplified Testing**: By centralizing logic in Actions, unit tests can focus on these Actions, effectively covering multiple use cases with a single set of tests.
7. **Scalability**: As the application grows, new features can be added as discrete Actions, easily integrable into existing workflows without affecting other parts of the codebase.
8. **Maintenance Benefits**: When business logic needs to be updated, changes can be made in one place (the Action) and automatically propagate to all areas where the Action is used.

This approach to using Actions has significantly improved our ability to maintain a consistent codebase across the Filament admin panel and our API, which is crucial for our mobile app integration. It has streamlined our development process, reduced potential for errors from duplicated code, and made our application more modular and easier to extend.

## Core Components

1. **Booking Management**: Uses Actions to handle prime and non-prime reservations, ensuring consistent logic across web interfaces and API endpoints.
2. **User Management**: Employs role-specific Actions for managing restaurants, concierges, and partners, maintaining consistency between admin operations and API functionalities.
3. **[Earnings/Booking Calculation](./docs/booking_calculations.md)**: Utilizes complex Actions for calculating and distributing earnings, which can be triggered from various parts of the application including scheduled tasks and manual admin operations.
4. **Reporting and Analytics**: Leverages Actions for data aggregation and processing, allowing the same reports to be generated for the admin panel and API responses.
5. **Notification System**: Uses notification-specific Actions that can be triggered from multiple contexts, ensuring consistent communication logic across the platform.
6. **Integration Layer**: Implements Actions for standardized data exchange with restaurant management systems, usable both in background jobs and real-time API interactions.

## Venue Management

The PRIMA platform provides robust tools for managing venues (soon to be refactored from restaurants to venues). This system allows for detailed control over various aspects of venue operations within the platform.

### Key Features of Venue Management

1. **Booking Hours Management**:
    - Set and manage regular operating hours
    - Define prime and non-prime time slots
    - Implement seasonal or special event hours

2. **Table Availability**:
    - Set total table count for the venue
    - Manage table availability for different time slots

3. **Fee Structure Management**:
    - Set booking fees for prime time slots
    - Define bounty fees for non-prime bookings
    - Implement pricing based on special events

4. **Venue Admin Management**:
    - Create and manage venue admin accounts
    - Audit logs for admin actions

5. **Integration with Booking System**:
    - Real-time updates of table availability
    - Automatic application of appropriate fees based on booking time
    - Notifications for venue admins about new bookings

## Referral System

The PRIMA platform incorporates a multi-level referral system that incentivizes growth and rewards key stakeholders.

### Partner Referrals

Partners can refer both venues and concierges to the platform:

1. **Venue Referrals**:
    - Partners earn a percentage of each booking made at venues they've referred
    - This percentage is taken from the total booking fee, not from the platform's earnings

2. **Concierge Referrals**:
    - Partners earn a percentage of each booking made by concierges they've referred
    - This percentage is taken from the total booking fee, not from the platform's earnings

### Concierge Referrals

Concierges can refer other concierges, creating a two-level referral structure:

1. **Level 1 Referrals**:
    - A concierge earns a percentage of the total booking fee from bookings made by concierges they directly referred
    - Typically a higher percentage than Level 2 referrals

2. **Level 2 Referrals**:
    - A concierge also earns a smaller percentage from bookings made by concierges referred by their Level 1 referrals
    - This creates an incentive for concierges to not only refer others but also support their success

### Referral Calculation Process

1. **Identify Referral Relationships**:
    - For each booking, check if the venue or concierge was referred by a partner
    - For concierge bookings, check for Level 1 and Level 2 referral relationships

2. **Calculate Referral Earnings**:
    - Apply the appropriate percentage to the total booking fee for each applicable referral
    - For concierge referrals, calculate both Level 1 and Level 2 earnings if applicable

3. **Record Referral Earnings**:
    - Create earning records for each referral payment
    - Update total earnings for partners and referring concierges

4. **Adjust Platform Earnings**:
    - The platform's earnings are what remains after all other percentages (restaurant, concierge, partner, and referral) have been distributed

## Getting Started

To set up the PRIMA platform locally, follow these steps:

### Prerequisites

Ensure you have the following installed on your development machine:

- **PHP**: Version 8.3 or higher
- **Composer**: Dependency manager for PHP
- **Node.js and npm**: For front-end dependencies
- **MySQL**: Version 8.0 or higher

### Installation Steps

1. **Clone the Repository**:
    ```bash
    git clone https://github.com/andruu/concierge
    cd concierge
    ```

2. **Install PHP Dependencies**:
   Use Composer to install the necessary PHP packages:
    ```bash
    composer install
    ```

3. **Install Front-End Dependencies**:
   Install the required Node.js packages using npm:
    ```bash
    npm install
    ```

4. **Set Up Environment Variables**:
   Create a `.env` file by copying the example file:
    ```bash
    cp .env.example .env
    ```
   Then, update the `.env` file with your local environment settings, such as database credentials.

5. **Generate Application Key**:
   Laravel requires an application key, which can be generated with:
    ```bash
    php artisan key:generate
    ```

6. **Run Database Migrations**:
   Set up your database structure by running the migrations:
    ```bash
    php artisan migrate
    ```

7. **Seed the Database** *(optional but recommended)*:
   Seed your database with initial data:
    ```bash
    php artisan db:seed
    ```

8. **Compile Front-End Assets**:
   Compile your front-end assets using Laravel Mix:
    ```bash
    npm run dev
    ```

9. **Serve the Application**:
   Start the Laravel development server:
    ```bash
    php artisan serve
    ```
   The application should now be accessible at `http://localhost:8000`.

10. **Access the Admin Panel**:
    Once the application is running, you can access the admin panel by navigating to `http://localhost:8000/admin`.

### Additional Setup

- **Horizon**: If you are using Laravel Horizon for managing queues, ensure it's set up correctly:
    ```bash
    php artisan horizon:watch
    ```

Now you are ready to start developing and testing the PRIMA platform locally.
