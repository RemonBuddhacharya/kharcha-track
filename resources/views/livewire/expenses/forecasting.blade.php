<?php

declare(strict_types=1);

use App\Models\Forecast;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public array $forecastData = [];
    public array $forecastChart = [];
    public array $categoryForecasts = [];
    public array $categoryCharts = [];
    public array $pastForecasts = [];
    public bool $showPastForecasts = false;
    public string $selectedMonth = '';
    public int $forecastMonths = 3;

    public function mount(): void
    {
        $this->loadForecasts();
    }

    public function loadForecasts(): void
    {
        $userId = Auth::id();

        // Get overall forecasts
        $this->forecastData = Forecast::forecastForUser($userId, $this->forecastMonths);

        // Prepare chart data for overall forecasts
        $labels = array_map(fn($f) => date('M Y', strtotime($f['forecast_date'])), $this->forecastData);
        $data = array_map(fn($f) => $f['predicted_amount'], $this->forecastData);
        $this->forecastChart = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Forecasted Expenses',
                    'data' => $data,
                    'backgroundColor' => '#38bdf8',
                    'borderColor' => '#0ea5e9',
                    'borderWidth' => 2,
                    'tension' => 0.1,
                    'fill' => false,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true],
                ],
            ],
        ];

        // Get category-specific forecasts
        $this->categoryForecasts = Forecast::forecastByCategory($userId, $this->forecastMonths);

        // Group forecasts by category
        $groupedForecasts = [];
        foreach ($this->categoryForecasts as $forecast) {
            $categoryId = $forecast['category_id'];
            if (!isset($groupedForecasts[$categoryId])) {
                $groupedForecasts[$categoryId] = [];
            }
            $groupedForecasts[$categoryId][] = $forecast;
        }

        // Prepare chart data for each category
        $this->categoryCharts = [];
        foreach ($groupedForecasts as $categoryId => $forecasts) {
            // Get category details
            $category = Category::find($categoryId);
            if (!$category) continue;

            $labels = array_map(fn($f) => date('M Y', strtotime($f['forecast_date'])), $forecasts);
            $data = array_map(fn($f) => $f['predicted_amount'], $forecasts);

            $this->categoryCharts[$categoryId] = [
                'name' => $category->name,
                'color' => $category->color,
                'chart' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $labels,
                        'datasets' => [[
                            'label' => $category->name . ' Forecast',
                            'data' => $data,
                            'backgroundColor' => $category->color ?? '#38bdf8',
                            'borderColor' => $category->color ?? '#0ea5e9',
                            'borderWidth' => 2,
                            'tension' => 0.1,
                            'fill' => false,
                        ]],
                    ],
                    'options' => [
                        'responsive' => true,
                        'plugins' => [
                            'legend' => ['display' => true],
                        ],
                    ],
                ]
            ];
        }

        // Load past forecasts if requested
        if ($this->showPastForecasts) {
            $this->loadPastForecasts();
        }
    }

    public function loadPastForecasts(): void
    {
        $userId = Auth::id();
        $now = now();

        // Get distinct months for which we have forecasts
        $months = DB::table('forecasts')
            ->select(DB::raw('DISTINCT TO_CHAR(forecast_date, \'YYYY-MM\') as month'))
            ->where('user_id', $userId)
            ->where('forecast_date', '<', $now)
            ->orderBy('month', 'desc')
            ->get()
            ->pluck('month')
            ->toArray();

        if (empty($months)) {
            $this->pastForecasts = [];
            return;
        }

        // If no month is selected, use the most recent one
        if (empty($this->selectedMonth)) {
            $this->selectedMonth = $months[0];
        }

        // Get forecasts for the selected month
        $startDate = $this->selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $this->pastForecasts = Forecast::where('user_id', $userId)
            ->whereBetween('forecast_date', [$startDate, $endDate])
            ->orderBy('forecast_date')
            ->get()
            ->toArray();
    }

    public function togglePastForecasts(): void
    {
        $this->showPastForecasts = !$this->showPastForecasts;
        if ($this->showPastForecasts) {
            $this->loadPastForecasts();
        }
    }

    public function updateSelectedMonth(): void
    {
        $this->loadPastForecasts();
    }

    public function updateForecastMonths(): void
    {
        $this->loadForecasts();
    }
}; ?>

<div>
    <x-header title="Expense Forecast" separator />

    <!-- Forecast Controls -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <label for="forecastMonths" class="mr-2">Forecast Months:</label>
            <select id="forecastMonths" wire:model="forecastMonths" wire:change="updateForecastMonths" class="select select-bordered select-sm">
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
            </select>
        </div>
        <button wire:click="togglePastForecasts" class="btn btn-sm btn-outline">
            {{ $showPastForecasts ? 'Hide Past Forecasts' : 'Show Past Forecasts' }}
        </button>
    </div>

    <!-- Overall Forecast Chart -->
    <x-card class="mb-6">
        <div class="font-semibold mb-2">Forecasted Expenses (Next {{ $forecastMonths }} Months)</div>
        <div class="text-sm text-gray-500 mb-4">
            Using moving average algorithm with data from the last 12 months
        </div>
        <x-chart wire:model="forecastChart" />
    </x-card>

    <!-- Overall Forecast Table -->
    <x-card class="mb-6">
        <div class="font-semibold mb-2">Overall Forecast Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Predicted Amount</th>
                        <th>Confidence</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($forecastData as $row)
                        <tr>
                            <td>{{ date('M Y', strtotime($row['forecast_date'])) }}</td>
                            <td>{{ number_format($row['predicted_amount'], 2) }}</td>
                            <td>{{ number_format($row['confidence_score'] * 100, 0) }}%</td>
                            <td>
                                @php
                                    $params = is_array($row['model_parameters']) ? $row['model_parameters'] : json_decode($row['model_parameters'], true);
                                @endphp
                                @if(isset($params['method']))
                                    {{ ucfirst(str_replace('_', ' ', $params['method'])) }}
                                @else
                                    Unknown
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Category Forecasts -->
    @if(count($categoryCharts) > 0)
        <x-header title="Category Forecasts" size="sm" separator class="mt-8 mb-4" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @foreach($categoryCharts as $categoryId => $categoryData)
                <x-card>
                    <div class="font-semibold mb-2" style="color: {{ $categoryData['color'] }}">
                        {{ $categoryData['name'] }} Forecast
                    </div>
                    <x-chart wire:model="categoryCharts.{{ $categoryId }}.chart" />
                </x-card>
            @endforeach
        </div>
    @endif

    <!-- Past Forecasts -->
    @if($showPastForecasts)
        <x-header title="Past Forecasts" size="sm" separator class="mt-8 mb-4" />

        <x-card class="mb-6">
            <div class="flex items-center mb-4">
                <label for="selectedMonth" class="mr-2">Select Month:</label>
                <select id="selectedMonth" wire:model="selectedMonth" wire:change="updateSelectedMonth" class="select select-bordered select-sm">
                    @php
                        $months = DB::table('forecasts')
                            ->select(DB::raw('DISTINCT TO_CHAR(forecast_date, \'YYYY-MM\') as month'))
                            ->where('user_id', Auth::id())
                            ->where('forecast_date', '<', now())
                            ->orderBy('month', 'desc')
                            ->get()
                            ->pluck('month')
                            ->toArray();
                    @endphp

                    @foreach($months as $month)
                        <option value="{{ $month }}">{{ date('F Y', strtotime($month . '-01')) }}</option>
                    @endforeach
                </select>
            </div>

            @if(count($pastForecasts) > 0)
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Predicted Amount</th>
                                <th>Confidence</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pastForecasts as $forecast)
                                <tr>
                                    <td>{{ date('d M Y', strtotime($forecast['forecast_date'])) }}</td>
                                    <td>
                                        @if($forecast['category_id'])
                                            {{ \App\Models\Category::find($forecast['category_id'])?->name ?? 'Unknown' }}
                                        @else
                                            Overall
                                        @endif
                                    </td>
                                    <td>{{ number_format($forecast['predicted_amount'], 2) }}</td>
                                    <td>{{ number_format($forecast['confidence_score'] * 100, 0) }}%</td>
                                    <td>
                                        @php
                                            $params = is_array($forecast['model_parameters']) ? $forecast['model_parameters'] : json_decode($forecast['model_parameters'], true);
                                        @endphp
                                        @if(isset($params['method']))
                                            {{ ucfirst(str_replace('_', ' ', $params['method'])) }}
                                        @else
                                            Unknown
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4 text-gray-500">
                    No past forecasts available for the selected month.
                </div>
            @endif
        </x-card>
    @endif
</div>
