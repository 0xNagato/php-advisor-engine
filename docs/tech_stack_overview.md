# PRIMA Platform - Tech Stack Overview

**PRIMA** is a comprehensive hospitality reservation platform built on a modern PHP stack:

## 🔧 Core Technology:

- **PHP 8.3+** with **Laravel 11** (full-stack framework)
- **PostgreSQL 17** (primary database with advanced JSON/materialized views)
- **Redis** (caching & sessions)

## 🎨 Frontend & UI:

- **Filament 3** (admin panel & CRUD)
- **Livewire 3** (real-time UI components)
- **TailwindCSS 3** (styling)
- **Vite** (asset compilation)

## 🏗️ Architecture Patterns:

- **Laravel Actions** (business logic encapsulation)
- **Spatie Laravel Data** (type-safe DTOs)
- **Event-driven architecture**
- **API-first design** with Laravel Sanctum
- **Queue-based processing** with Laravel Horizon

## 🔌 Key Integrations:

- **Stripe** (payments)
- **Twilio** (SMS/communications)
- **Restaurant management systems** (CoverManager, Restoo APIs)
- **Multi-currency & timezone support**

## 🧪 Development & Testing:

- **Pest** (testing framework)
- **Laravel Telescope** (debugging)
- **PHPStan** (static analysis)
- **Laravel Herd** (local development)

## 📊 Business Logic:

- Complex multi-stakeholder revenue calculations
- Automated booking approval systems
- Real-time availability synchronization
- Customer attribution & referral systems

---

We have comprehensive documentation in our `docs/` folder covering booking calculations, platform integrations, and system architecture. The platform handles sophisticated hospitality workflows including prime/non-prime booking management, earnings distribution, and restaurant system integrations.
