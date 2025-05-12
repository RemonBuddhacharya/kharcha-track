<?php

use App\Models\Forecast;
use App\Models\User;
use App\Models\Expense;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('generates forecasts using moving average algorithm', function () {
    // Create a user
    $user = User::factory()->create();

    // Create a category
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Category',
        'color' => '#ff0000',
    ]);

    // Create expenses for the last 6 months
    $now = Carbon::now();
    $amounts = [100, 200, 300, 400, 500, 600];

    foreach ($amounts as $index => $amount) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => $amount,
            'date' => $now->copy()->subMonths(6 - $index)->startOfMonth(),
        ]);
    }

    // Generate forecasts
    $forecasts = Forecast::forecastForUser($user->id, 3);

    // Check that we have 3 forecasts
    expect($forecasts)->toHaveCount(3);

    // Check that the first forecast uses moving average of the last 3 months (300, 400, 500)
    $movingAvg = round((300 + 400 + 500) / 3, 2);
    expect($forecasts[0]['predicted_amount'])->toBe($movingAvg);

    // Check that forecasts are saved to the database
    $dbForecasts = Forecast::where('user_id', $user->id)->get();
    expect($dbForecasts)->toHaveCount(3);
});

it('returns existing forecasts if available', function () {
    // Create a user
    $user = User::factory()->create();

    // Create some forecasts
    $now = Carbon::now();
    for ($i = 1; $i <= 3; $i++) {
        Forecast::create([
            'user_id' => $user->id,
            'category' => null,
            'predicted_amount' => 100 * $i,
            'forecast_date' => $now->copy()->addMonths($i)->startOfMonth(),
            'confidence_score' => 0.8,
            'model_parameters' => json_encode(['method' => 'test']),
        ]);
    }

    // Get forecasts
    $forecasts = Forecast::forecastForUser($user->id, 3);

    // Check that we get the existing forecasts
    expect($forecasts)->toHaveCount(3);
    expect($forecasts[0]['predicted_amount'])->toBe(100.0);
    expect($forecasts[1]['predicted_amount'])->toBe(200.0);
    expect($forecasts[2]['predicted_amount'])->toBe(300.0);
});

it('generates category-specific forecasts', function () {
    // Create a user
    $user = User::factory()->create();

    // Create two categories
    $category1 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Category 1',
    ]);

    $category2 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Category 2',
    ]);

    // Create expenses for each category
    $now = Carbon::now();

    // Category 1: 100, 200, 300
    for ($i = 0; $i < 3; $i++) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'amount' => 100 * ($i + 1),
            'date' => $now->copy()->subMonths(3 - $i)->startOfMonth(),
        ]);
    }

    // Category 2: 400, 500, 600
    for ($i = 0; $i < 3; $i++) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'amount' => 400 + (100 * $i),
            'date' => $now->copy()->subMonths(3 - $i)->startOfMonth(),
        ]);
    }

    // Generate category forecasts
    $forecasts = Forecast::forecastByCategory($user->id, 1);

    // Check that we have forecasts for both categories
    $dbForecasts = Forecast::where('user_id', $user->id)
        ->whereNotNull('category')
        ->get();

    expect($dbForecasts)->toHaveCount(2);

    // Check that the forecasts use the correct moving averages
    $cat1Forecast = $dbForecasts->firstWhere('category', $category1->id);
    $cat2Forecast = $dbForecasts->firstWhere('category', $category2->id);

    expect($cat1Forecast->predicted_amount)->toBe(200.0); // (100 + 200 + 300) / 3
    expect($cat2Forecast->predicted_amount)->toBe(500.0); // (400 + 500 + 600) / 3
});
