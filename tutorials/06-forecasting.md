# Expense Forecasting System

This tutorial will guide you through implementing the expense forecasting system in the KharchaTrack application. We'll create a service to handle forecasting calculations and a Livewire component to display the forecasts.

## Understanding the Forecasting System

Our forecasting system will use the Moving Average algorithm to predict future expenses based on historical spending patterns. This is a simple yet effective forecasting method that works well for expense prediction.

## Creating the Forecasting Service

First, let's create a service class that will handle our forecasting logic.

### Step 1: Create the Service Directory

Let's create a directory for our services:

```bash
mkdir -p app/Services
```

### Step 2: Create the ForecastingService Class

Create a new file `app/Services/ForecastingService.php` with the following content:

```php
<?php

namespace App\Services;

class ForecastingService
{
    /**
     * Calculate moving average for a data set
     * 
     * @param array $data The data array
     * @param int $window The window size
     * @return array Moving average values
     */
    public function movingAverage(array $data, int $window = 3)
    {
        $result = [];
        $count = count($data);
        
        if ($count < $window) {
            return $result;
        }
        
        for ($i = 0; $i <= $count - $window; $i++) {
            $sum = 0;
            for ($j = 0; $j < $window; $j++) {
                $sum += $data[$i + $j];
            }
            $result[] = $sum / $window;
        }
        
        return $result;
    }
    
    /**
     * Predict next month's expenses using moving average
     * 
     * @param array $amounts Previous expense amounts
     * @param int $window Window size for calculation
     * @return float|null Predicted amount
     */
    public function predictNextMonth(array $amounts, int $window = 3)
    {
        // Use the last X months to predict the next month
        $recentAmounts = array_slice($amounts, -$window);
        
        if (count($recentAmounts) < $window) {
            return null;
        }
        
        return array_sum($recentAmounts) / count($recentAmounts);
    }
    
    /**
     * Forecast expenses for the next several months
     * 
     * @param array $amounts Previous expense amounts
     * @param int $forecastPeriod Number of months to forecast
     * @param int $window Window size for calculation
     * @return array Forecasted amounts
     */
    public function forecastExpenses(array $amounts, int $forecastPeriod = 3, int $window = 3)
    {
        $forecasts = [];
        
        if (count($amounts) < $window) {
            return $forecasts;
        }
        
        $workingData = $amounts;
        
        for ($i = 0; $i < $forecastPeriod; $i++) {
            $recentAmounts = array_slice($workingData, -$window);
            $forecast = array_sum($recentAmounts) / count($recentAmounts);
            $forecasts[] = $forecast;
            $workingData[] = $forecast;
        }
        
        return $forecasts;
    }
}
```

## Implementing the Expense Forecasting Component

Now, let's implement the Livewire component for displaying expense forecasts.

### Step 1: Update the ExpenseForecasting Component

Open `app/Http/Livewire/ExpenseForecasting.php` and update it with the following code:

```php
<?php

namespace App\Http\Livewire;

use App\Models\Expense;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ForecastingService;

class ExpenseForecasting extends Component
{
    public $monthlyExpenses = [];
    public $forecastedExpenses = [];
    public $nextMonthForecast = 0;
    public $forecastPeriod = 3; // Number of months to forecast
    public $windowSize = 3; // Window size for moving average
    
    public function mount(ForecastingService $forecastService)
    {
        $this->updateForecasts($forecastService);
    }
    
    public function updatedWindowSize()
    {
        $this->updateForecasts(app(ForecastingService::class));
    }
    
    public function updatedForecastPeriod()
    {
        $this->updateForecasts(app(ForecastingService::class));
    }
    
    public function updateForecasts(ForecastingService $forecastService)
    {
        $userId = Auth::id();
        
        // Get monthly expense data
        $monthlyData = Expense::where('user_id', $userId)
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
            
        $this->monthlyExpenses = [];
        
        foreach ($monthlyData as $data) {
            $this->monthlyExpenses[$data->month] = round($data->total, 2);
        }
        
        // Get amounts only for forecasting
        $amounts = array_values($this->monthlyExpenses);
        
        // Calculate forecasted values
        $this->forecastedExpenses = [];
        
        // Use moving average to forecast
        $movingAverages = $forecastService->movingAverage($amounts, $this->windowSize);
        
        // Add known months with their forecasted values
        $months = array_keys($this->monthlyExpenses);
        $lastMonthIndex = count($months) - 1;
        
        for ($i = $this->windowSize - 1; $i < count($movingAverages); $i++) {
            $monthIndex = $i - ($this->windowSize - 1) + $lastMonthIndex - (count($movingAverages) - 1);
            if (isset($months[$monthIndex])) {
                $this->forecastedExpenses[$months[$monthIndex]] = round($movingAverages[$i], 2);
            }
        }
        
        // Add future forecasted months
        if (!empty($months)) {
            $lastMonth = end($months);
            $lastMonthDate = \DateTime::createFromFormat('Y-m', $lastMonth);
            
            for ($i = 1; $i <= $this->forecastPeriod; $i++) {
                $lastMonthDate->modify('+1 month');
                $nextMonth = $lastMonthDate->format('Y-m');
                
                // Calculate next month's forecast using last X months
                $recentAmounts = array_slice($amounts, -$this->windowSize);
                if (count($recentAmounts) >= $this->windowSize) {
                    $forecast = array_sum($recentAmounts) / count($recentAmounts);
                    
                    $this->forecastedExpenses[$nextMonth] = round($forecast, 2);
                    $amounts[] = $forecast; // Add forecast to amounts for next iteration
                }
            }
            
            // Set next month forecast (for notification)
            if (!empty($this->forecastedExpenses)) {
                $this->nextMonthForecast = array_values($this->forecastedExpenses)[0] ?? 0;
            }
        }
    }
    
    public function render()
    {
        return view('livewire.expense-forecasting');
    }
}
```

### Step 2: Create the ExpenseForecasting View

Now, let's create the view for our forecasting component. Create or update the file `resources/views/livewire/expense-forecasting.blade.php`:

```php
<div>
    <x-mary-card>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Expense Forecasting</h2>
            
            <div class="flex space-x-4">
                <x-mary-select wire:model="windowSize" label="Window Size" class="w-36">
                    <option value="2">2 Months</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">12 Months</option>
                </x-mary-select>
                
                <x-mary-select wire:model="forecastPeriod" label="Forecast Period" class="w-36">
                    <option value="1">1 Month</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">12 Months</option>
                </x-mary-select>
            </div>
        </div>
        
        <div class="mb-6">
            <x-mary-alert color="info" title="About Forecasting">
                <p>Forecasts are calculated using the Moving Average algorithm based on your past {{ $windowSize }} months of expense data.</p>
            </x-mary-alert>
        </div>
        
        @if(count($monthlyExpenses) >= $windowSize)
            <div class="mb-6">
                <x-mary-card title="Expense Forecast">
                    <div class="chart-container h-80">
                        <canvas id="forecastChart"></canvas>
                    </div>
                    
                    <script>
                        document.addEventListener('livewire:load', function() {
                            const renderChart = function() {
                                if (window.forecastChart) {
                                    window.forecastChart.destroy();
                                }
                                
                                const ctx = document.getElementById('forecastChart').getContext('2d');
                                
                                const monthlyExpenses = @json($monthlyExpenses);
                                const forecastedExpenses = @json($forecastedExpenses);
                                
                                // Prepare data
                                const months = Object.keys(monthlyExpenses);
                                const actuals = Object.values(monthlyExpenses);
                                
                                const forecastMonths = Object.keys(forecastedExpenses);
                                const forecasts = Object.values(forecastedExpenses);
                                
                                // Find the overlap point
                                const lastActualMonth = months[months.length - 1];
                                let overlapIndex = forecastMonths.indexOf(lastActualMonth);
                                if (overlapIndex === -1) overlapIndex = 0;
                                
                                // Prepare datasets
                                const actualData = [];
                                const forecastData = [];
                                
                                months.forEach((month, i) => {
                                    actualData.push({
                                        x: month,
                                        y: actuals[i]
                                    });
                                    
                                    // Add null for forecast line until overlap
                                    if (forecastMonths.includes(month)) {
                                        const fIndex = forecastMonths.indexOf(month);
                                        forecastData.push({
                                            x: month,
                                            y: forecasts[fIndex]
                                        });
                                    } else {
                                        forecastData.push({
                                            x: month,
                                            y: null
                                        });
                                    }
                                });
                                
                                // Add future forecast months
                                const futureMonths = overlapIndex > -1 
                                    ? forecastMonths.slice(overlapIndex + 1) 
                                    : forecastMonths;
                                    
                                futureMonths.forEach((month, i) => {
                                    actualData.push({
                                        x: month,
                                        y: null
                                    });
                                    
                                    forecastData.push({
                                        x: month,
                                        y: forecasts[overlapIndex + 1 + i]
                                    });
                                });
                                
                                // Combine all labels
                                const allMonths = [...new Set([...months, ...forecastMonths])];
                                
                                // Create chart
                                window.forecastChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: allMonths,
                                        datasets: [
                                            {
                                                label: 'Actual Expenses',
                                                data: actualData,
                                                borderColor: '#3b82f6',
                                                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                                borderWidth: 2,
                                                pointBorderColor: '#3b82f6',
                                                pointBackgroundColor: '#fff',
                                                pointBorderWidth: 2,
                                                pointRadius: 4
                                            },
                                            {
                                                label: 'Forecasted Expenses',
                                                data: forecastData,
                                                borderColor: '#f59e0b',
                                                borderDash: [5, 5],
                                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                                borderWidth: 2,
                                                pointBorderColor: '#f59e0b',
                                                pointBackgroundColor: '#fff',
                                                pointBorderWidth: 2,
                                                pointRadius: 4
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        interaction: {
                                            mode: 'index',
                                            intersect: false
                                        },
                                        scales: {
                                            x: {
                                                ticks: {
                                                    callback: function(value, index, values) {
                                                        const date = new Date(allMonths[index] + '-01');
                                                        return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
                                                    }
                                                }
                                            },
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    callback: function(value) {
                                                        return '$' + value.toFixed(2);
                                                    }
                                                }
                                            }
                                        },
                                        plugins: {
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            };
                            
                            renderChart();
                            
                            Livewire.on('forecastUpdated', renderChart);
                            window.addEventListener('resize', renderChart);
                            
                            // Handle component refresh
                            const component = window.Livewire.find(
                                document.getElementById('forecastChart').closest('[wire\\:id]').getAttribute('wire:id')
                            );
                            if (component) {
                                component.on('propertyUpdated', renderChart);
                            }
                        });
                    </script>
                </x-mary-card>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-mary-card title="Next Month Forecast">
                    <div class="flex items-center justify-center py-6">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-primary">${{ number_format($nextMonthForecast, 2) }}</div>
                            <div class="text-sm text-gray-500 mt-2">Forecasted expense for next month</div>
                        </div>
                    </div>
                </x-mary-card>
                
                <x-mary-card title="Forecast Table">
                    <div class="overflow-x-auto">
                        <x-mary-table striped>
                            <x-slot:header>
                                <x-mary-table-heading>Month</x-mary-table-heading>
                                <x-mary-table-heading>Actual</x-mary-table-heading>
                                <x-mary-table-heading>Forecast</x-mary-table-heading>
                                <x-mary-table-heading>Variance</x-mary-table-heading>
                            </x-slot:header>
                            
                            @foreach(array_slice(array_keys($forecastedExpenses), -6) as $month)
                                <x-mary-table-row>
                                    <x-mary-table-cell>
                                        {{ date('M Y', strtotime($month . '-01')) }}
                                    </x-mary-table-cell>
                                    <x-mary-table-cell>
                                        @if(isset($monthlyExpenses[$month]))
                                            ${{ number_format($monthlyExpenses[$month], 2) }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </x-mary-table-cell>
                                    <x-mary-table-cell>
                                        ${{ number_format($forecastedExpenses[$month], 2) }}
                                    </x-mary-table-cell>
                                    <x-mary-table-cell>
                                        @if(isset($monthlyExpenses[$month]))
                                            @php
                                                $variance = $monthlyExpenses[$month] - $forecastedExpenses[$month];
                                                $varPercentage = $forecastedExpenses[$month] != 0 ? 
                                                    ($variance / $forecastedExpenses[$month]) * 100 : 0;
                                            @endphp
                                            
                                            <span class="{{ $variance < 0 ? 'text-success' : 'text-error' }}">
                                                {{ $variance < 0 ? '-' : '+' }}${{ number_format(abs($variance), 2) }}
                                                ({{ number_format(abs($varPercentage), 1) }}%)
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </x-mary-table-cell>
                                </x-mary-table-row>
                            @endforeach
                        </x-mary-table>
                    </div>
                </x-mary-card>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <x-mary-icon name="o-exclamation-circle" class="w-16 h-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium">Not Enough Data</h3>
                <p class="mt-2 text-gray-500">
                    You need at least {{ $windowSize }} months of expense data to generate forecasts.
                    You currently have {{ count($monthlyExpenses) }} months of data.
                </p>
                <x-mary-button link="{{ route('expenses.create') }}" color="primary" class="mt-6">
                    Add More Expenses
                </x-mary-button>
            </div>
        @endif
    </x-mary-card>
</div>
```

## Understanding the Moving Average Algorithm

The Moving Average algorithm is a simple but effective time series forecasting method. Here's how it works:

1. It takes a window (a specific number of periods) of past data points
2. Calculates the average of those points
3. Uses that average as the forecast for the next period
4. For subsequent periods, the window slides forward to include the most recent data

For example, with a 3-month window:
- To predict April, average January, February, and March
- To predict May, average February, March, and April
- And so on...

This is a good baseline forecasting method that works well with expense data because:
- It smooths out short-term fluctuations
- It captures recent trends
- It's easy to understand and implement
- It works well for data without strong seasonality

## Testing the Forecasting System

Now that we have implemented the forecasting system, let's test it:

1. Make sure you have at least 3 months of expense data in your system
2. Login to your application
3. Navigate to the Forecasting page to see your expense forecasts
4. Try adjusting the window size and forecast period to see how it affects the predictions

## What You've Learned

- How to implement a forecasting service using the Moving Average algorithm
- How to visualize historical and forecasted data with Chart.js
- How to create an interactive forecasting system with adjustable parameters
- How to implement complex charts with multiple datasets in Livewire

## Next Steps

In the next tutorial, we'll implement the anomaly detection system to identify unusual spending patterns.

[Next Tutorial: Anomaly Detection →](07-anomaly-detection.md)

[← Back to Index](../README.md)