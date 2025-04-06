# Authentication & User Management for KharchaTrack

This tutorial will guide you through setting up authentication and user management in the KharchaTrack application using Laravel Breeze with Livewire.

## Understanding the Authentication System

We've already installed Laravel Breeze with Livewire in the first tutorial. Breeze provides a simple, minimal implementation of Laravel's authentication features including login, registration, password reset, email verification, and more.

## Step 1: Understanding the Auth Routes

Laravel Breeze has already registered the necessary routes for authentication. Let's examine the routes in `routes/auth.php`:

```php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
```

## Step 2: Setting Up Application Routes

Let's update our main routes in `routes/web.php` to include routes for our application with proper middleware:

```php
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Expenses
Route::middleware(['auth'])->prefix('expenses')->group(function () {
    Route::get('/', function () {
        return view('expenses.index');
    })->name('expenses.index');
    
    Route::get('/create', function () {
        return view('expenses.create');
    })->name('expenses.create');
    
    Route::get('/{expense}/edit', function ($expense) {
        return view('expenses.edit', ['expense_id' => $expense]);
    })->name('expenses.edit');
});

// Forecasting
Route::get('/forecasting', function () {
    return view('forecasting');
})->middleware(['auth'])->name('forecasting');

// Anomalies
Route::get('/anomalies', function () {
    return view('anomalies');
})->middleware(['auth'])->name('anomalies');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
    
    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');
    
    Route::get('/logs', function () {
        return view('admin.logs');
    })->name('admin.logs');
    
    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');
});

require __DIR__.'/auth.php';
```

Notice the use of middleware:
- `auth` - Ensures that the user is authenticated
- `role:admin` - Uses Spatie Laravel Permission to check if the user has the 'admin' role

## Step 3: Creating a Middleware for Role Checking

We need to create a middleware to check if a user has a specific role. Spatie Laravel Permission has already registered this middleware for us, but let's examine how it works and make sure it's registered.

In the file `app/Http/Kernel.php`, make sure that the following is in the `$routeMiddleware` array:

```php
'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
```

If these entries aren't present, add them.

## Step 4: Creating the Admin Views

Let's create some basic views for the admin section. First, we need to create the directory structure:

```bash
mkdir -p resources/views/admin
```

Now let's create a simple admin dashboard view. Create a file `resources/views/admin/dashboard.blade.php`:

```php
<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Welcome to the Admin Dashboard</h3>
                    <p>From here, you can manage all aspects of the KharchaTrack application.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <x-mary-card title="User Management">
                            <p>Manage users and their roles</p>
                            <x-mary-button link="{{ route('admin.users') }}" class="mt-4">Manage Users</x-mary-button>
                        </x-mary-card>
                        
                        <x-mary-card title="System Logs">
                            <p>View system activity logs</p>
                            <x-mary-button link="{{ route('admin.logs') }}" class="mt-4">View Logs</x-mary-button>
                        </x-mary-card>
                        
                        <x-mary-card title="Settings">
                            <p>Configure application settings</p>
                            <x-mary-button link="{{ route('admin.settings') }}" class="mt-4">Manage Settings</x-mary-button>
                        </x-mary-card>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## Step 5: Customizing the Main Navigation

Let's update the navigation menu to include links to our main features. Open the file `resources/views/layouts/navigation.blade.php` (which was created by Laravel Breeze) and update it:

```php
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    
                    <x-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                        {{ __('Expenses') }}
                    </x-nav-link>
                    
                    <x-nav-link :href="route('forecasting')" :active="request()->routeIs('forecasting')">
                        {{ __('Forecasting') }}
                    </x-nav-link>
                    
                    <x-nav-link :href="route('anomalies')" :active="request()->routeIs('anomalies')">
                        {{ __('Anomalies') }}
                    </x-nav-link>
                    
                    @role('admin')
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Admin') }}
                    </x-nav-link>
                    @endrole
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                {{ __('Expenses') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link :href="route('forecasting')" :active="request()->routeIs('forecasting')">
                {{ __('Forecasting') }}
            </x-responsive-nav-link>
            
            <x-responsive-nav-link :href="route('anomalies')" :active="request()->routeIs('anomalies')">
                {{ __('Anomalies') }}
            </x-responsive-nav-link>
            
            @role('admin')
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                {{ __('Admin') }}
            </x-responsive-nav-link>
            @endrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
```

## Step 6: Redirecting Users Based on Roles

Let's update the RedirectIfAuthenticated middleware to redirect users to the appropriate dashboard based on their role. Open `app/Http/Middleware/RedirectIfAuthenticated.php` and update the `handle` method:

```php
public function handle(Request $request, Closure $next, string ...$guards): Response
{
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            $user = Auth::guard($guard)->user();
            
            // Redirect to admin dashboard if user is admin
            if ($user->hasRole('admin')) {
                return redirect(RouteServiceProvider::ADMIN_HOME);
            }
            
            // Redirect to user dashboard
            return redirect(RouteServiceProvider::HOME);
        }
    }

    return $next($request);
}
```

Now let's update the `RouteServiceProvider` to include an admin home constant. Open `app/Providers/RouteServiceProvider.php` and add this constant:

```php
public const HOME = '/dashboard';
public const ADMIN_HOME = '/admin/dashboard';
```

## Step 7: Creating User Profile Management

Laravel Breeze already provides a basic user profile management page. Let's enhance it a bit to show the user's role.

Open `resources/views/profile/partials/update-profile-information-form.blade.php` and add this after the email input:

```php
<!-- User Role -->
<div class="mt-4">
    <x-input-label for="role" :value="__('Role')" />
    <x-text-input id="role" class="block mt-1 w-full bg-gray-100" type="text" :value="auth()->user()->getRoleNames()->join(', ')" disabled readonly />
</div>
```

## Step 8: Testing the Authentication System

Let's test our authentication system by running the application and trying to:

1. Register a new user
2. Log in as the admin user
3. Log in as a regular user
4. Test role-based access controls

```bash
php artisan serve
```

Then visit `http://localhost:8000/login` and log in with:

- Admin: admin@kharchatrack.com / password
- User: user@kharchatrack.com / password

## What You've Learned

- How Laravel Breeze configures authentication routes and middleware
- How to set up role-based access control using Spatie Laravel Permission
- How to customize navigation based on user roles
- How to create admin-specific views
- How to redirect users based on their roles

## Next Steps

In the next tutorial, we'll set up the frontend components using Mary UI to create our dashboard and expense management interfaces.

[Next Tutorial: Frontend Development →](04-frontend.md)

[← Back to Index](../README.md)