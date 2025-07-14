<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Forecast;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

it('renders the forecasting component for authenticated users', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/forecast')
        ->assertOk()
        ->assertSee('Expense Forecast');
});

it('loads forecasts for the user', function () {
    $user = User::factory()->create();

    // Create some expenses
    $now = Carbon::now();
    for ($i = 1; $i <= 3; $i++) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 100 * $i,
            'date' => $now->copy()->subMonths($i)->startOfMonth(),
        ]);
    }

    // Test the component
    Volt::actingAs($user)
        ->test('expenses.forecasting')
        ->assertSee('Forecasted Expenses')
        ->assertSee('Overall Forecast Table')
        ->assertSee('Using moving average algorithm');
});

it('displays category forecasts', function () {
    $user = User::factory()->create();

    // Create a category
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Category',
        'color' => '#ff0000',
    ]);

    // Create some expenses for the category
    $now = Carbon::now();
    for ($i = 1; $i <= 3; $i++) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100 * $i,
            'date' => $now->copy()->subMonths($i)->startOfMonth(),
        ]);
    }

    // Test the component
    Volt::actingAs($user)
        ->test('expenses.forecasting')
        ->assertSee('Category Forecasts')
        ->assertSee('Test Category Forecast');
});

it('can toggle past forecasts', function () {
    $user = User::factory()->create();

    // Create some past forecasts
    $now = Carbon::now();
    for ($i = 1; $i <= 3; $i++) {
        Forecast::create([
            'user_id' => $user->id,
            'category' => null,
            'predicted_amount' => 100 * $i,
            'forecast_date' => $now->copy()->subMonths($i)->startOfMonth(),
            'confidence_score' => 0.8,
            'model_parameters' => json_encode(['method' => 'test']),
        ]);
    }

    // Test the component
    $component = Volt::actingAs($user)
        ->test('expenses.forecasting');

    // Initially, past forecasts should not be visible
    $component->assertDontSee('Past Forecasts');

    // Toggle past forecasts
    $component->call('togglePastForecasts');

    // Now past forecasts should be visible
    $component->assertSee('Past Forecasts');
});

it('can change forecast months', function () {
    $user = User::factory()->create();

    // Create some expenses
    $now = Carbon::now();
    for ($i = 1; $i <= 6; $i++) {
        Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 100 * $i,
            'date' => $now->copy()->subMonths($i)->startOfMonth(),
        ]);
    }

    // Test the component
    $component = Volt::actingAs($user)
        ->test('expenses.forecasting');

    // Initially, it should show 3 months forecast
    $component->assertSee('Next 3 Months');

    // Change to 6 months
    $component->set('forecastMonths', 6)
        ->call('updateForecastMonths');

    // Now it should show 6 months forecast
    $component->assertSee('Next 6 Months');
});
