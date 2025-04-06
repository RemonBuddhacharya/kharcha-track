# Anomaly Detection System

This tutorial will guide you through implementing the anomaly detection system in the KharchaTrack application. We'll create a service to detect unusual spending patterns and a Livewire component to display the anomalies.

## Understanding Anomaly Detection

Anomaly detection is the process of identifying data points that deviate significantly from the majority of the data. In the context of expense tracking, anomalies represent unusual spending patterns that might indicate:

1. Data entry errors
2. Fraudulent transactions
3. One-time large expenses
4. Changes in spending habits

Our implementation will use a statistical approach based on Z-scores to identify outliers in expense data.

## Creating the Anomaly Detection Service

First, let's create a service class that will handle our anomaly detection logic.

### Step 1: Create the AnomalyDetectionService Class

Create a new file `app/Services/AnomalyDetectionService.php` with the following content:

```php
<?php

namespace App\Services;

class AnomalyDetectionService
{
    /**
     * Detect anomalies in expense data using statistical methods
     * 
     * @param array $amounts Array of expense amounts
     * @param float $threshold Z-score threshold for anomaly detection
     * @return array Indices of anomalous entries
     */
    public function detectAnomalies(array $amounts, $threshold = 2.0)
    {
        $anomalies = [];
        
        if (count($amounts) < 5) {
            return $anomalies;
        }
        
        // Calculate mean and standard deviation
        $mean = array_sum($amounts) / count($amounts);
        
        $variance = 0;
        foreach ($amounts as $amount) {
            $variance += pow(($amount - $mean), 2);
        }
        $stdDev = sqrt($variance / count($amounts));
        
        if ($stdDev == 0) {
            return $anomalies;
        }
        
        // Detect anomalies using Z-score
        foreach ($amounts as $index => $amount) {
            $zScore = abs(($amount - $mean) / $stdDev);
            
            if ($zScore > $threshold) {
                $anomalies[] = $index;
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Detect anomalies grouped by category
     * 
     * @param array $data Array of [category => [amounts]]
     * @param float $threshold Z-score threshold for anomaly detection
     * @return array Array of [category => [anomaly_indices]]
     */
    public function detectAnomaliesByCategory(array $data, $threshold = 2.0)
    {
        $result = [];
        
        foreach ($data as $category => $amounts) {
            $result[$category] = $this->detectAnomalies($amounts, $threshold);
        }
        
        return $result;
    }
}
```

## Understanding the Z-Score Method

Z-score is a statistical measurement that describes a value's relationship to the mean of a group of values. It's measured in terms of standard deviations from the mean.

The formula for Z-score is:
```
Z = (X - μ) / σ
```

Where:
- X is the data point
- μ is the mean of the data
- σ is the standard deviation

In our implementation:
1. We calculate the mean and standard deviation of all expense amounts
2. For each expense, we calculate its Z-score
3. If the absolute value of the Z-score exceeds our threshold (default 2.0), we mark it as an anomaly

A threshold of 2.0 means we're flagging expenses that are more than 2 standard deviations away from the mean, which typically captures the outlying 5% of a normal distribution.

## Implementing the Anomaly Detection Component

Now, let's implement the Livewire component for displaying detected anomalies.

### Step 1: Update the AnomalyDetection Component

Create or update the file `app/Http/Livewire/AnomalyDetection.php` with the following code:

```php
<?php

namespace App\Http\Livewire;

use App\Models\Expense;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Services\AnomalyDetectionService;

class AnomalyDetection extends Component
{
    use WithPagination;
    
    public $threshold = 2.0;
    public $dateFrom = '';
    public $dateTo = '';
    public $category = '';
    public $perPage = 10;
    
    public $categories = [];
    public $totalAnomalies = 0;
    
    protected $queryString = [
        'threshold' => ['except' => 2.0],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'category' => ['except' => ''],
    ];
    
    public function mount()
    {
        $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        
        $this->categories = Expense::where('user_id', Auth::id())
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
    
    public function updatedThreshold()
    {
        $this->resetPage();
    }
    
    public function updatedCategory()
    {
        $this->resetPage();
    }
    
    public function updatedDateFrom()
    {
        $this->resetPage();
    }
    
    public function updatedDateTo()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset(['category']);
        $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->threshold = 2.0;
        $this->resetPage();
    }
    
    public function render(AnomalyDetectionService $anomalyService)
    {
        $userId = Auth::id();
        
        $query = Expense::where('user_id', $userId);
        
        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }
        
        if ($this->category) {
            $query->where('category', $this->category);
        }
        
        $expenses = $query->orderBy('date', 'asc')->get();
        
        $amounts = $expenses->pluck('amount')->toArray();
        $anomalyIndices = $anomalyService->detectAnomalies($amounts, $this->threshold);
        
        $anomalyIds = [];
        foreach ($anomalyIndices as $index) {
            if (isset($expenses[$index])) {
                $anomalyIds[] = $expenses[$index]->id;
                
                // Update anomaly status in database
                $expenses[$index]->is_anomaly = true;
                $expenses[$index]->save();
            }
        }
        
        $this->totalAnomalies = count($anomalyIds);
        
        // Get paginated anomalies
        $anomalies = Expense::where('user_id', $userId)
            ->whereIn('id', $anomalyIds)
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);
        
        return view('livewire.anomaly-detection', [
            'anomalies' => $anomalies,
        ]);
    }
}
```

### Step 2: Create the AnomalyDetection View

Create or update the file `resources/views/livewire/anomaly-detection.blade.php` with the following content:

```php
<div>
    <x-mary-card>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Anomaly Detection</h2>
        </div>
        
        <div class="mb-6">
            <x-mary-alert color="info" title="About Anomaly Detection">
                <p>Anomalies are unusual expenses that deviate significantly from your normal spending patterns. They are detected using statistical methods and may indicate errors, fraud, or one-time large expenses.</p>
            </x-mary-alert>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="md:col-span-2">
                <x-mary-range wire:model="threshold" label="Sensitivity Threshold" min="1" max="4" step="0.1" value="2" />
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Higher Sensitivity</span>
                    <span>Lower Sensitivity</span>
                </div>
            </div>
            
            <div>
                <x-mary-select wire:model="category" placeholder="All Categories">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </x-mary-select>
            </div>
            
            <div>
                <div class="flex space-x-2">
                    <x-mary-input wire:model="dateFrom" type="date" placeholder="From Date" class="w-1/2" />
                    <x-mary-input wire:model="dateTo" type="date" placeholder="To Date" class="w-1/2" />
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <x-mary-table striped>
                <x-slot:header>
                    <x-mary-table-heading>Date</x-mary-table-heading>
                    <x-mary-table-heading>Title</x-mary-table-heading>
                    <x-mary-table-heading>Category</x-mary-table-heading>
                    <x-mary-table-heading>Amount</x-mary-table-heading>
                    <x-mary-table-heading>Details</x-mary-table-heading>
                </x-slot:header>
                
                @forelse($anomalies as $anomaly)
                    <x-mary-table-row highlight>
                        <x-mary-table-cell>
                            {{ $anomaly->date->format('M d, Y') }}
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <div class="flex items-center">
                                <span>{{ $anomaly->title }}</span>
                                <x-mary-badge color="error" size="sm" class="ml-2">Anomaly</x-mary-badge>
                            </div>
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <x-mary-badge color="primary">{{ $anomaly->category }}</x-mary-badge>
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <span class="font-semibold">{{ number_format($anomaly->amount, 2) }}</span>
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <x-mary-button link="{{ route('expenses.edit', $anomaly->id) }}" icon="o-eye" size="sm" color="info">
                                View
                            </x-mary-button>
                        </x-mary-table-cell>
                    </x-mary-table-row>
                @empty
                    <x-mary-table-row>
                        <x-mary-table-cell colspan="5" class="text-center py-4">
                            <div class="flex flex-col items-center justify-center">
                                <x-mary-icon name="o-check-circle" class="w-12 h-12 text-success" />
                                <p class="mt-2 text-gray-500">No anomalies detected with current settings</p>
                            </div>
                        </x-mary-table-cell>
                    </x-mary-table-row>
                @endforelse
            </x-mary-table>
        </div>
        
        <div class="mt-4">
            {{ $anomalies->links() }}
        </div>
        
        <div class="flex justify-between items-center mt-6">
            <x-mary-button wire:click="resetFilters" color="ghost" icon="o-x-mark" label="Reset Filters" />
            
            <div>
                <span class="text-sm text-gray-500">
                    {{ $totalAnomalies }} anomalies detected out of all expenses
                </span>
            </div>
        </div>
    </x-mary-card>
</div>
```

## Adding AnomalyDetection to ExpenseList Component

Now, let's update the ExpenseList component to use our anomaly detection service to identify anomalies in the expense list.

Open `app/Http/Livewire/ExpenseList.php` and update the render method to detect anomalies:

```php
public function render(AnomalyDetectionService $anomalyService)
{
    $userId = Auth::id();
    
    $query = Expense::where('user_id', $userId);
    
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('title', 'like', '%' . $this->search . '%')
              ->orWhere('description', 'like', '%' . $this->search . '%');
        });
    }
    
    if ($this->category) {
        $query->where('category', $this->category);
    }
    
    if ($this->dateFrom) {
        $query->where('date', '>=', $this->dateFrom);
    }
    
    if ($this->dateTo) {
        $query->where('date', '<=', $this->dateTo);
    }
    
    $expenses = $query->orderBy($this->sortField, $this->sortDirection)
        ->paginate($this->perPage);
        
    // Check for anomalies
    $allExpenses = Expense::where('user_id', $userId)
        ->orderBy('date', 'asc')
        ->pluck('amount')
        ->toArray();
        
    $anomalies = $anomalyService->detectAnomalies($allExpenses);
    
    // Mark anomalies in the current page
    $expenseIds = $expenses->pluck('id')->toArray();
    foreach ($expenses as $index => $expense) {
        $position = array_search($expense->id, $expenseIds);
        $expense->is_anomaly = in_array($position, $anomalies);
        if ($expense->is_anomaly) {
            $expense->save(); // Save anomaly status to database
        }
    }
    
    return view('livewire.expense-list', [
        'expenses' => $expenses,
    ]);
}
```

Make sure to add the AnomalyDetectionService to the use statements at the top of the file:

```php
use App\Services\AnomalyDetectionService;
```

## Setting Up Route and Navigation

Let's add a route for our anomaly detection page. Open `routes/web.php` and add the following route:

```php
Route::get('/anomalies', function () {
    return view('anomalies');
})->middleware(['auth'])->name('anomalies');
```

Create the view file `resources/views/anomalies.blade.php`:

```php
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Anomaly Detection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:anomaly-detection />
        </div>
    </div>
</x-app-layout>
```

## Testing the Anomaly Detection System

Now that we have implemented the anomaly detection system, let's test it:

1. Make sure you have some expenses with varying amounts in your system
2. Login to your application
3. Navigate to the Anomalies page to see detected anomalies
4. Try adjusting the sensitivity threshold to see how it affects the detection
5. Notice how anomalies are also highlighted in the expense list

## What You've Learned

- How to implement anomaly detection using statistical methods
- How to create a service class for complex calculations
- How to visualize and filter anomalies
- How to use Z-scores to identify outliers in data
- How to update multiple components to use the same service

## Next Steps

Congratulations! You have successfully built a complete expense tracking application with forecasting and anomaly detection capabilities. Here are some ideas for further improvements:

1. Add email notifications when anomalies are detected
2. Implement more advanced forecasting methods
3. Add data export functionality
4. Create a mobile app version
5. Implement budgeting features

## Complete Project Overview

You've now completed all the tutorials for the KharchaTrack application. Let's review what we've built:

1. A complete Laravel application with authentication
2. User roles and permissions
3. Expense management with CRUD operations
4. Interactive dashboard with charts
5. Expense forecasting using Moving Average algorithm
6. Anomaly detection using Z-score statistics
7. A beautiful UI with Mary UI components
8. Real-time interactions using Livewire

This application demonstrates how to combine web development with data analysis techniques to create a powerful tool for personal finance management.

[← Back to Index](../README.md)