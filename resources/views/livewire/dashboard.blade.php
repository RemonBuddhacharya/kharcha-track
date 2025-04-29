<?php

declare(strict_types=1);

use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
new 
#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class extends Component {
    public $totalUsers;
    public $totalExpenses;
    public $totalCategories;
    public $totalAnomalies;
    public $totalForecasts;

    public $userExpenses;
    public $userCategories;
    public $userAnomalies;

    // Chart state for Mary UI <x-chart>
    public array $systemExpenseTrendChart = [];
    public array $userExpenseTrendChart = [];

    public function mount(): void
    {
        if (Auth::user()->hasRole('admin')) {
            $this->totalUsers = DB::table('users')->count();
            $this->totalExpenses = Expense::sum('amount');
            $this->totalCategories = DB::table('categories')->count();
            $this->totalAnomalies = DB::table('anomalies')->count();
            $this->totalForecasts = DB::table('forecasts')->count();
            // Example: Fetch and prepare system-wide expense trend data
            $this->systemExpenseTrendChart = [
                'type' => 'line',
                'data' => [
                    'labels' => $this->getLast12Months(),
                    'datasets' => [
                        [
                            'label' => 'System Expenses',
                            'data' => $this->getSystemExpensesForLast12Months(),
                            'backgroundColor' => '#3b82f6',
                            'borderColor' => '#2563eb',
                            'borderWidth' => 2,
                            'tension' => 0.4,
                        ]
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => ['display' => true],
                    ],
                ],
            ];
        } else {
            $userId = Auth::id();
            $this->userExpenses = Expense::where('user_id', $userId)->sum('amount');
            $this->userCategories = DB::table('categories')->where('user_id', $userId)->count();
            $this->userAnomalies = DB::table('anomalies')->where('user_id', $userId)->count();
            // Example: Fetch and prepare user expense trend data
            $this->userExpenseTrendChart = [
                'type' => 'line',
                'data' => [
                    'labels' => $this->getLast12Months(),
                    'datasets' => [
                        [
                            'label' => 'Your Expenses',
                            'data' => $this->getUserExpensesForLast12Months($userId),
                            'backgroundColor' => '#f59e42',
                            'borderColor' => '#ea580c',
                            'borderWidth' => 2,
                            'tension' => 0.4,
                        ]
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => ['display' => true],
                    ],
                ],
            ];
        }
    }

    // Example helpers (implement as needed)
    private function getLast12Months(): array
    {
        return collect(range(0, 11))
            ->map(fn($i) => Carbon::now()->subMonths(11 - $i)->format('M Y'))
            ->toArray();
    }
    private function getSystemExpensesForLast12Months(): array
    {
        $expenses = 
            Expense::query()
                ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
                ->where('date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

        $totals = collect(range(0, 11))
            ->map(function ($i) use ($expenses) {
                $date = Carbon::now()->subMonths(11 - $i);
                $match = $expenses->first(fn($e) => (int)$e->year === $date->year && (int)$e->month === $date->month);
                return $match ? (float) $match->total : 0.0;
            })
            ->toArray();
        return $totals;
    }
    private function getUserExpensesForLast12Months(int $userId): array
    {
        $expenses =
            Expense::query()
                ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
                ->where('user_id', $userId)
                ->where('date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

        $totals = collect(range(0, 11))
            ->map(function ($i) use ($expenses) {
                $date = Carbon::now()->subMonths(11 - $i);
                $match = $expenses->first(fn($e) => (int)$e->year === $date->year && (int)$e->month === $date->month);
                return $match ? (float) $match->total : 0.0;
            })
            ->toArray();
        return $totals;
    }
}; ?>
<div>
    <x-header title="Dashboard" separator progress-indicator></x-header>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-6">
        @role('admin')
            <!-- Admin Overview Cards -->
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-users" class="w-8 h-8 text-primary" />
                    <div>
                        <div class="text-2xl font-bold">{{ $totalUsers }}</div>
                        <div class="text-gray-500">Total Users</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-banknotes" class="w-8 h-8 text-success" />
                    <div>
                        <div class="text-2xl font-bold">{{ number_format($totalExpenses, 2) }}</div>
                        <div class="text-gray-500">Total Expenses (NPR)</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-tag" class="w-8 h-8 text-warning" />
                    <div>
                        <div class="text-2xl font-bold">{{ $totalCategories }}</div>
                        <div class="text-gray-500">Total Categories</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-beaker" class="w-8 h-8 text-error" />
                    <div>
                        <div class="text-2xl font-bold">{{ $totalAnomalies }}</div>
                        <div class="text-gray-500">Total Anomalies</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-presentation-chart-line" class="w-8 h-8 text-info" />
                    <div>
                        <div class="text-2xl font-bold">{{ $totalForecasts }}</div>
                        <div class="text-gray-500">Total Forecasts</div>
                    </div>
                </div>
            </x-card>
            <!-- System-wide Expense Trend Chart -->
            <x-card class="col-span-1 xl:col-span-2">
                <div class="font-semibold mb-2">System Expense Trend (Last 12 Months)</div>
                <x-chart wire:model="systemExpenseTrendChart" />
            </x-card>
        @else
            <!-- User Overview Cards -->
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-banknotes" class="w-8 h-8 text-success" />
                    <div>
                        <div class="text-2xl font-bold">{{ number_format($userExpenses, 2) }}</div>
                        <div class="text-gray-500">Your Total Expenses (NPR)</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-tag" class="w-8 h-8 text-warning" />
                    <div>
                        <div class="text-2xl font-bold">{{ $userCategories }}</div>
                        <div class="text-gray-500">Your Categories</div>
                    </div>
                </div>
            </x-card>
            <x-card class="col-span-1">
                <div class="flex items-center gap-4">
                    <x-icon name="o-beaker" class="w-8 h-8 text-error" />
                    <div>
                        <div class="text-2xl font-bold">{{ $userAnomalies }}</div>
                        <div class="text-gray-500">Your Anomalies</div>
                    </div>
                </div>
            </x-card>
            <!-- User Expense Trend Chart -->
            <x-card class="col-span-1 xl:col-span-2">
                <div class="font-semibold mb-2">Your Expense Trend (Last 12 Months)</div>
                <x-chart wire:model="userExpenseTrendChart" />
            </x-card>
        @endrole
    </div>
</div>
