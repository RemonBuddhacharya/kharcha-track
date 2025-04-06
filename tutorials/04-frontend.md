# Frontend Development with Mary UI

This tutorial will guide you through setting up the frontend of the KharchaTrack application using Mary UI, a component library built on top of Tailwind CSS and DaisyUI.

## Setting Up the Layout

We need to create a custom layout that incorporates Mary UI components. In KharchaTrack, we'll use Mary UI's sidebar layout for better navigation and organization.

### Step 1: Create a Custom App Layout

First, let's modify the main application layout to use Mary UI components. Create a new file at `resources/views/layouts/app.blade.php` (or modify the existing one if it was created by Laravel Breeze):

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'KharchaTrack') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-base-200">
        <x-mary-layout class="pt-0">
            <x-slot:sidebar>
                <x-mary-sidebar>
                    <img src="{{ asset('images/logo.png') }}" alt="KharchaTrack Logo" class="w-32 mx-auto my-4">
                    
                    <x-mary-sidebar-item title="Dashboard" icon="o-home" link="{{ route('dashboard') }}" />
                    <x-mary-sidebar-item title="Expenses" icon="o-currency-dollar" link="{{ route('expenses.index') }}" />
                    <x-mary-sidebar-item title="Forecasting" icon="o-chart-bar" link="{{ route('forecasting') }}" />
                    <x-mary-sidebar-item title="Anomalies" icon="o-exclamation-circle" link="{{ route('anomalies') }}" />
                    
                    @role('admin')
                    <x-mary-menu title="Admin" icon="o-cog">
                        <x-mary-menu-item title="Users" icon="o-users" link="{{ route('admin.users') }}" />
                        <x-mary-menu-item title="System Log" icon="o-document-text" link="{{ route('admin.logs') }}" />
                        <x-mary-menu-item title="Settings" icon="o-adjustments" link="{{ route('admin.settings') }}" />
                    </x-mary-menu>
                    @endrole
                    
                    <div class="mt-auto border-t border-base-300 pt-2">
                        <x-mary-sidebar-item title="Profile" icon="o-user" link="{{ route('profile.edit') }}" />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-mary-sidebar-item title="Logout" icon="o-logout" link="{{ route('logout') }}" 
                                onclick="event.preventDefault(); this.closest('form').submit();" />
                        </form>
                    </div>
                </x-mary-sidebar>
            </x-slot:sidebar>
            
            <main>
                @if (isset($header))
                    <header class="bg-white shadow mb-6">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
                
                {{ $slot }}
            </main>
        </x-mary-layout>
    </div>
    
    <x-mary-notifications position="bottom-right" />
    
    @livewireScripts
</body>
</html>
```

### Step 2: Create a Basic Logo

Let's create a simple logo for our application. First, make sure the images directory exists:

```bash
mkdir -p public/images
```

You can create a simple logo or download one. For now, let's place a placeholder (you can replace this with a real logo later):

```bash
# This is a command you'd execute on your own to create a logo
# You might want to use a proper graphic design tool instead
```

### Step 3: Update the Dashboard View

Now, let's create a beautiful dashboard page. Create or update the file at `resources/views/dashboard.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:expense-dashboard />
        </div>
    </div>
</x-app-layout>
```

## Creating Directory Structure for Views

Let's create the necessary directory structure for our views:

```bash
mkdir -p resources/views/expenses
mkdir -p resources/views/forecasting
mkdir -p resources/views/anomalies
mkdir -p resources/views/admin/users
mkdir -p resources/views/admin/logs
mkdir -p resources/views/admin/settings
```

## Creating Basic View Files

### Step 1: Create Expense Views

Create the following files for expense management:

1. `resources/views/expenses/index.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Expenses') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:expense-list />
        </div>
    </div>
</x-app-layout>
```

2. `resources/views/expenses/create.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Expense') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:expense-form />
            
            <div class="mt-6 text-right">
                <x-mary-button link="{{ route('expenses.index') }}" color="ghost" icon="o-arrow-left">
                    Back to Expenses
                </x-mary-button>
            </div>
        </div>
    </div>
</x-app-layout>
```

3. `resources/views/expenses/edit.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Expense') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:expense-form :expense_id="$expense_id" />
            
            <div class="mt-6 text-right">
                <x-mary-button link="{{ route('expenses.index') }}" color="ghost" icon="o-arrow-left">
                    Back to Expenses
                </x-mary-button>
            </div>
        </div>
    </div>
</x-app-layout>
```

### Step 2: Create Forecasting View

Create the forecasting view at `resources/views/forecasting.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Expense Forecasting') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:expense-forecasting />
        </div>
    </div>
</x-app-layout>
```

### Step 3: Create Anomaly Detection View

Create the anomaly detection view at `resources/views/anomalies.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Anomaly Detection') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:anomaly-detection />
        </div>
    </div>
</x-app-layout>
```

### Step 4: Create Admin Views

Create the following admin views:

1. `resources/views/admin/users.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-lg font-medium mb-4">User Management</h3>
                <p class="mb-4">Manage users and their roles in the system.</p>
                
                <!-- User management will be implemented later -->
                <div class="bg-base-200 p-6 rounded-lg text-center">
                    <p class="text-gray-500">User management interface will be implemented here.</p>
                </div>
                
                <div class="mt-6 text-right">
                    <x-mary-button link="{{ route('admin.dashboard') }}" color="ghost" icon="o-arrow-left">
                        Back to Admin Dashboard
                    </x-mary-button>
                </div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
```

2. `resources/views/admin/logs.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Logs') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-lg font-medium mb-4">System Logs</h3>
                <p class="mb-4">View system activity logs and events.</p>
                
                <!-- Logs interface will be implemented later -->
                <div class="bg-base-200 p-6 rounded-lg text-center">
                    <p class="text-gray-500">System logs interface will be implemented here.</p>
                </div>
                
                <div class="mt-6 text-right">
                    <x-mary-button link="{{ route('admin.dashboard') }}" color="ghost" icon="o-arrow-left">
                        Back to Admin Dashboard
                    </x-mary-button>
                </div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
```

3. `resources/views/admin/settings.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot:header>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-lg font-medium mb-4">System Settings</h3>
                <p class="mb-4">Configure global application settings.</p>
                
                <!-- Settings interface will be implemented later -->
                <div class="bg-base-200 p-6 rounded-lg text-center">
                    <p class="text-gray-500">System settings interface will be implemented here.</p>
                </div>
                
                <div class="mt-6 text-right">
                    <x-mary-button link="{{ route('admin.dashboard') }}" color="ghost" icon="o-arrow-left">
                        Back to Admin Dashboard
                    </x-mary-button>
                </div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
```

## Setting Up JavaScript Dependencies

To make our charts and UI interactive, let's set up some JavaScript dependencies.

### Step 1: Install Chart.js

Chart.js is a powerful library for creating beautiful charts. Let's install it:

```bash
npm install chart.js
```

### Step 2: Configure Chart.js in app.js

Update your `resources/js/app.js` file to include Chart.js:

```js
import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
```

### Step 3: Build Assets

Run the build command to compile your assets:

```bash
npm run build
```

## Creating Basic Livewire Directory Structure

We need to create the directory structure for our Livewire components:

```bash
mkdir -p app/Http/Livewire
```

## Testing the Frontend

Now that we have set up our basic frontend structure, let's test it by running the application:

```bash
php artisan serve
```

Visit `http://localhost:8000/login` and log in to see the new UI.

## Next Steps: Adding Placeholder Components

The views we've created reference Livewire components that we haven't created yet. Let's create some placeholder components so that we can see the UI without errors.

### Step 1: Create Placeholder Livewire Component Files

We'll create placeholder files for the Livewire components we'll implement in detail later:

```bash
php artisan make:livewire ExpenseDashboard
php artisan make:livewire ExpenseList
php artisan make:livewire ExpenseForm
php artisan make:livewire ExpenseForecasting
php artisan make:livewire AnomalyDetection
```

### Step 2: Update the Placeholder Components

For now, let's just add simple placeholder content to each component. We'll implement the full functionality in later tutorials.

1. Update `app/Http/Livewire/ExpenseDashboard.php`:

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ExpenseDashboard extends Component
{
    public function render()
    {
        return view('livewire.expense-dashboard', [
            'totalExpenses' => 0,
            'monthlySummary' => [],
            'categorySummary' => [],
            'recentExpenses' => [],
            'anomalies' => [],
        ]);
    }
}
```

2. Create `resources/views/livewire/expense-dashboard.blade.php`:

```php
<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-mary-stat 
            title="Total Expenses" 
            value="0.00" 
            icon="o-currency-dollar" 
            color="primary"
            desc="Period: Month" />
            
        <x-mary-stat 
            title="Forecasted Next Month" 
            value="N/A" 
            icon="o-chart-bar" 
            color="accent"
            desc="Based on spending patterns" />
            
        <x-mary-stat 
            title="Anomalies Detected" 
            value="0" 
            icon="o-exclamation-circle" 
            color="success"
            desc="No unusual spending detected" />
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-card title="Expense Categories">
            <div class="flex flex-col items-center justify-center h-64">
                <x-mary-icon name="o-chart-pie" class="w-12 h-12 text-gray-400" />
                <p class="mt-2 text-gray-500">No expense data yet</p>
                <x-mary-button link="{{ route('expenses.create') }}" color="primary" class="mt-4">Add Your First Expense</x-mary-button>
            </div>
        </x-mary-card>
        
        <x-mary-card title="Recent Activity">
            <div class="flex flex-col items-center justify-center h-64">
                <x-mary-icon name="o-clock" class="w-12 h-12 text-gray-400" />
                <p class="mt-2 text-gray-500">No recent activity</p>
                <x-mary-button link="{{ route('expenses.create') }}" color="primary" class="mt-4">Add Your First Expense</x-mary-button>
            </div>
        </x-mary-card>
    </div>
</div>
```

## What You've Learned

- How to set up the frontend structure using Mary UI
- How to create a custom app layout with a sidebar
- How to create blade views for different sections of the application
- How to set up Chart.js for data visualization
- How to create placeholder Livewire components

## Next Steps

In the next tutorial, we'll implement the full functionality of the expense management Livewire components.

[Next Tutorial: Expense Management →](05-expense-management.md)

[← Back to Index](../README.md)