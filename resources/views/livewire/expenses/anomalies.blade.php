<?php

declare(strict_types=1);

use App\Models\Anomaly;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public array $anomalies = [];
    public array $anomalyChart = [];
    public string $dateFrom;
    public string $dateTo;
    public float $threshold = 2.0;
    public bool $loading = false;

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
                'plugins' => [
                    'legend' => [
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Expense Anomaly Detection (Isolation Forest)',
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
                                'day' => 'MMM d, yyyy'
                            ]
                        ],
                        'title' => [
                            'display' => true,
                            'text' => 'Date',
                        ],
                    ],
                    'y' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Amount',
                        ],
                    ],
                ],
            ],
        ];
        $this->loading = false;
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
            <x-chart wire:model="anomalyChart" />
        @endif
    </x-card>
    <x-card>
        <div class="font-semibold mb-2">Anomalies Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expense ID</th>
                        <th>Amount</th>
                        <th>Anomaly Status</th>
                        <th>Score</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($anomalies as $row)
                        <tr class="{{ $row['is_anomaly'] ? 'bg-red-50' : '' }}">
                            <td>{{ $row['reviewed_at'] ? \Illuminate\Support\Carbon::parse($row['reviewed_at'])->format('Y-m-d') : '-' }}</td>
                            <td>{{ $row['expense_id'] }}</td>
                            <td>{{ $row['amount'] }}</td>
                            <td>{{ $row['is_anomaly'] ? 'Anomaly' : 'Normal' }}</td>
                            <td>{{ $row['anomaly_score'] > 0 ? $row['anomaly_score'] : '-' }}</td>
                            <td>{{ $row['reason'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
