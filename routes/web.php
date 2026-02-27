<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Landing page - accessible to all
Route::livewire('/', 'landing')->name('landing');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'auth.login')->name('login');
    Route::livewire('/register', 'auth.register')->name('register');
    Route::livewire('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'auth.reset-password')->name('password.reset');
});

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        throw new AuthorizationException;
    }

    if ($user->hasVerifiedEmail()) {
        return redirect('/');
    }

    $user->markEmailAsVerified();
    $user->previously_verified = true;
    $user->save();

    if (! Auth::check()) {
        $message = $user->previously_verified
        ? 'Welcome back! Your new email address has been verified.'
        : 'Email verification completed successfully!';
        Auth::login($user);
    } else {
        $message = $user->previously_verified
        ? 'New Email address has been verified for '.$user->name.'.'
        : 'Email verification completed successfully for '.$user->name.'.';
    }

    $user->sendEmailVerificationNotification();

    return redirect('/')->with('verified', $message);
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// Email verification routes
Route::middleware('auth')->group(function () {
    Route::livewire('/email/verify', 'auth.verify-email')->name('verification.notice');
});

// Routes that require authentication but not email verification
Route::middleware('auth')->group(function () {
    Route::livewire('/profile', 'profile')->name('profile');
    Route::livewire('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:access dashboard');
    Route::livewire('/logout', 'auth.logout')->name('logout');
});

// Protected routes requiring email verification
Route::middleware(['auth', 'verified'])->group(function () {

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/users', 'admin.users.index')->name('users.index');
        Route::livewire('/roles', 'admin.roles.index')->name('roles.index');
        Route::livewire('/permissions', 'admin.permissions.index')->name('permissions.index');
    });

    Route::livewire('/forecast', 'expenses.forecasting')->name('forecast.index');
    Route::livewire('/anomaly', 'expenses.anomalies')->name('anomaly.index');
    // Regular user routes
    Route::livewire('/expenses', 'expenses.index')->name('expenses.index');
    Route::livewire('/expenses/create', 'expenses.create')->name('expenses.create');
    Route::livewire('/expenses/{id}/edit', 'expenses.edit')->name('expenses.edit');

    Route::livewire('/categories', 'categories.index')->name('categories.index');
});
