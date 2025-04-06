# Getting Started with KharchaTrack

Welcome to the first tutorial in the KharchaTrack series! This tutorial will guide you through setting up your development environment and creating the initial project structure.

## Prerequisites

Before starting, make sure you have the following installed:

- PHP 8.2 or higher
- Composer
- Node.js and npm
- PostgreSQL
- Git

## Step 1: Install Laravel

First, ensure you have the Laravel installer:

```bash
composer global require laravel/installer
```

## Step 2: Create a New Laravel Project

Create a new Laravel project called KharchaTrack:

```bash
laravel new KharchaTrack
cd KharchaTrack
```

This will create a new Laravel project with the latest version.

## Step 3: Configure Database Connection

Open the `.env` file in your project root and update the database configuration to use PostgreSQL:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kharcha_track
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Make sure to change `your_password` to your actual PostgreSQL password.

## Step 4: Create the PostgreSQL Database

Create a new PostgreSQL database for our project:

```bash
# Connect to PostgreSQL
psql -U postgres

# Create the database
CREATE DATABASE kharcha_track;

# Exit PostgreSQL
\q
```

## Step 5: Install Required Packages

Our project requires several packages for functionality. Install them using Composer:

```bash
# Install Livewire for interactive components
composer require livewire/livewire

# Install PHP-ML for machine learning capabilities
composer require php-ai/php-ml

# Install Laravel Breeze with Livewire for authentication
composer require laravel/breeze --dev
php artisan breeze:install livewire

# Install Spatie Laravel Permission for roles and permissions
composer require spatie/laravel-permission

# Install Mary UI for frontend styling
composer require robsontenorio/mary
php artisan mary:install
```

## Step 6: Install and Build Frontend Dependencies

Install and build the frontend dependencies:

```bash
npm install
npm run build
```

## Step 7: Start the Development Server

Start the Laravel development server:

```bash
php artisan serve
```

Now you can access your application at `http://localhost:8000`.

## Understanding the Project Structure

Let's take a moment to understand the key directories in our Laravel project:

- `app/` - Contains the core code of your application
  - `Http/Controllers/` - Contains controller classes
  - `Http/Livewire/` - Contains Livewire component classes
  - `Models/` - Contains Eloquent model classes
  - `Services/` - We'll create this directory for our service classes
- `resources/` - Contains views, raw assets, and language files
  - `views/` - Contains Blade templates
  - `views/livewire/` - Contains Livewire component views
- `routes/` - Contains route definitions
- `database/` - Contains migrations, factories, and seeders
- `public/` - Contains publicly accessible files and compiled assets
- `config/` - Contains configuration files

## What You've Learned

In this tutorial, you've learned how to:
- Set up a new Laravel project
- Configure PostgreSQL database connection
- Install required packages
- Start the development server

## Next Steps

In the next tutorial, we'll set up the database with migrations and create the necessary models for our application.

[← Back to Index](../README.md)