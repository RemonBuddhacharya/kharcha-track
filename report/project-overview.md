# Project Overview — Kharcha Track

## What this project is
Kharcha Track is a modern expense tracking application built with **Laravel 12** and **Livewire 4**. It provides a real-time, server-rendered UI using **MaryUI** and **Tailwind CSS**, with role-based access control via **Spatie Permissions**.

## Core Features (from README + code)
- Expense management (list, create, edit, delete)
- Category management
- Forecasting and anomaly detection for expenses
- Role-based admin tools (users/roles/permissions)
- Authentication with email verification
- Responsive UI with dark/light theme support

## Key Tech Stack
- **Backend**: Laravel 12, PHP 8.4 (see `composer.json`)
- **UI**: Livewire 4 + MaryUI + Tailwind CSS
- **Auth / API**: Laravel Sanctum (installed)
- **Other**: Octane, PWA support, NativePHP mobile

## High-Level Architecture
- **Routes**: Livewire routes in `routes/web.php`; minimal API routes in `routes/api.php`.
- **Livewire Components**: Inline components defined inside Blade views under `resources/views/livewire/**`.
- **Models**: `Expense`, `Category`, `Forecast`, `Anomaly`, `ExpenseHistory`, `User` under `app/Models`.
- **Business Logic**: Many component actions currently call Eloquent directly (e.g., expenses CRUD).

## Notable Domain Logic
- **Forecasting**: `Forecast::forecastForUser()` uses a moving-average approach over historical expenses.
- **Anomaly Detection**: `Anomaly::detectForUser()` uses a simplified isolation-forest-like score to flag outliers.
- **History Tracking**: `Expense` model auto-creates history records on create/update.

## How to run (summary)
1. `composer install`
2. `yarn install`
3. `cp .env.example .env && php artisan key:generate`
4. Configure DB + `php artisan migrate`
5. `composer dev` (runs PHP server, queue, pail, Vite)

## Where to look next
- **Expenses UI**: `resources/views/livewire/expenses/index.blade.php`
- **Routes**: `routes/web.php` and `routes/api.php`
- **Models**: `app/Models/*`
