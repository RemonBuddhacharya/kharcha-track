<?php

declare(strict_types=1);

use App\Models\Anomaly;
use App\Models\Expense;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public array $anomalies = [];
    public array $anomalyChart = [];
    public string $dateFrom;
    public string $dateTo;
    public float $threshold = 2.0;
    public bool $loading = false;
    public int $perPage = 10;
    public bool $detailDrawer = false;
    public ?Expense $selectedExpense = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->analyze();
    }

    public function analyze(): void
    {
        $this->loading = true;
        $userId = Auth::id();
        $this->anomalies = Anomaly::detectForUser($userId, $this->threshold, $this->dateFrom, $this->dateTo);
        $this->resetPage();

        // Separate normal expenses and anomalies
        $normalExpenses = collect($this->anomalies)->filter(function ($expense) {
            return !$expense['is_anomaly'];
        });

        $anomalies = collect($this->anomalies)->filter(function ($expense) {
            return $expense['is_anomaly'];
        });

        // Prepare scatter plot data for normal expenses (blue)
        $normalPoints = array_values($normalExpenses->map(function ($expense) {
            return [
                'x' => $expense['reviewed_at'] ? Carbon::parse($expense['reviewed_at'])->timestamp * 1000 : now()->timestamp * 1000,
                'y' => (int)$expense['amount'],
            ];
        })->toArray());

        // Prepare scatter plot data for anomalies (red)
        $anomalyPoints = array_values($anomalies->map(function ($anomaly) {
            return [
                'x' => $anomaly['reviewed_at'] ? Carbon::parse($anomaly['reviewed_at'])->timestamp * 1000 : now()->timestamp * 1000,
                'y' => (int)$anomaly['amount'],
            ];
        })->toArray());

        $this->anomalyChart = [
            'type' => 'scatter',
            'data' => [
                'datasets' => [
                    [
                        'label' => 'Normal Expenses',
                        'data' => $normalPoints,
                        'backgroundColor' => '#3b82f6', // Blue color
                        'borderColor' => '#2563eb',
                        'showLine' => false,
                    ],
                    [
                        'label' => 'Anomalies',
                        'data' => $anomalyPoints,
                        'backgroundColor' => '#f87171', // Red color
                        'borderColor' => '#dc2626',
                        'showLine' => false,
                    ]
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'top',
                        'labels' => ['boxWidth' => 12, 'padding' => 10, 'font' => ['size' => 11]],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Expense Anomaly Detection (Isolation Forest)',
                        'font' => ['size' => 13],
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'title' => "function(context) {
                                return new Date(context[0].parsed.x).toLocaleDateString();
                            }",
                            'label' => "function(context) {
                                return 'Amount: ' + context.parsed.y;
                            }"
                        ]
                    ]
                ],
                'scales' => [
                    'x' => [
                        'type' => 'time',
                        'time' => [
                            'unit' => 'day',
                            'displayFormats' => [
                                'day' => 'MMM d'
                            ]
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'Date',
                            'font' => ['size' => 11],
                        ],
                        'ticks' => ['maxRotation' => 45, 'font' => ['size' => 10]],
                    ],
                    'y' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Amount',
                            'font' => ['size' => 11],
                        ],
                        'ticks' => ['font' => ['size' => 10]],
                    ],
                ],
            ],
        ];
        $this->loading = false;
    }

    public function paginatedAnomalies(): LengthAwarePaginator
    {
        $currentPage = $this->getPage();
        $anomalies = collect($this->anomalies);

        return new LengthAwarePaginator(
            items: $anomalies->forPage($currentPage, $this->perPage)->values(),
            total: $anomalies->count(),
            perPage: $this->perPage,
            currentPage: $currentPage,
            options: [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function viewExpense(int $expenseId): void
    {
        $this->selectedExpense = Expense::query()
            ->with('category')
            ->where('user_id', Auth::id())
            ->findOrFail($expenseId);

        $this->detailDrawer = true;
    }
}; ?>

<div>
    <x-header title="Expense Anomalies" separator />
    <x-card class="mb-6">
        <form wire:submit.prevent="analyze" class="flex flex-wrap gap-4 items-end mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">From</label>
                <input type="date" wire:model.defer="dateFrom" class="input input-bordered" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">To</label>
                <input type="date" wire:model.defer="dateTo" class="input input-bordered" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Threshold</label>
                <input type="number" step="0.1" min="0.1" wire:model.defer="threshold" class="input input-bordered w-24" />
            </div>
            <button type="submit" class="btn btn-primary">Analyze</button>
            @if($loading)
                <span class="ml-2 text-info">Analyzing...</span>
            @endif
        </form>
        @if(!empty($anomalyChart['data']['datasets'][0]['data']) || !empty($anomalyChart['data']['datasets'][1]['data']))
            <div class="font-semibold mb-2">Expense Anomaly Detection using Isolation Forest Algorithm</div>
            <p class="text-sm text-gray-600 mb-4">This scatter plot shows all expenses (blue) and detected anomalies (red) based on the Isolation Forest algorithm.</p>
            <div class="relative h-96 md:h-96 lg:h-80">
                <x-chart wire:model="anomalyChart" />
            </div>
        @endif
    </x-card>
    <x-card>
        <div class="font-semibold mb-2">Anomalies Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Anomaly Status</th>
                        <th>Score</th>
                        <th>Reason</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->paginatedAnomalies() as $row)
                        <tr class="{{ $row['is_anomaly'] ? 'text-error' : '' }}">
                            <td>{{ $row['reviewed_at'] ? \Illuminate\Support\Carbon::parse($row['reviewed_at'])->format('Y-m-d') : '-' }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['amount'] }}</td>
                            <td>{{ $row['is_anomaly'] ? 'Anomaly' : 'Normal' }}</td>
                            <td>{{ $row['anomaly_score'] > 0 ? $row['anomaly_score'] : '-' }}</td>
                            <td>{{ $row['reason'] ?? '-' }}</td>
                            <td class="text-center">
                                <x-button
                                    icon="o-eye"
                                    class="btn-ghost btn-sm"
                                    title="View Expense"
                                    @click="$wire.viewExpense({{ $row['expense_id'] }})"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-mary-pagination
            :rows="$this->paginatedAnomalies()"
            :per-page-values="[5, 10, 15, 25, 50]"
            wire:model.live="perPage"
        />
    </x-card>

    <x-drawer wire:model="detailDrawer" title="Expense Details" subtitle="Review the selected expense" right separator
        with-close-button>
        @if ($selectedExpense)
            <div class="space-y-6">
                <div class="bg-base-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <span class="text-sm text-gray-500">Title</span>
                            <p class="font-medium">{{ $selectedExpense->title }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Amount</span>
                            <p class="font-medium">{{ number_format($selectedExpense->amount, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Category</span>
                            <p class="font-medium">{{ $selectedExpense->category?->name ?? 'Uncategorized' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Date</span>
                            <p class="font-medium">{{ $selectedExpense->date->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Payment Method</span>
                            <p class="font-medium">{{ $selectedExpense->payment_method ?: 'Not specified' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Recurring</span>
                            <p class="font-medium">{{ $selectedExpense->is_recurring ? 'Yes' : 'No' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Anomaly Flag</span>
                            <p class="font-medium">{{ $selectedExpense->is_anomaly ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <span class="text-sm text-gray-500">Description</span>
                    <p class="mt-1 rounded-lg bg-base-200 p-4 text-sm leading-6">
                        {{ $selectedExpense->description ?: 'No description provided.' }}
                    </p>
                </div>
            </div>
        @endif

        <x-slot:actions>
            <x-button label="Close" @click="$wire.detailDrawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
