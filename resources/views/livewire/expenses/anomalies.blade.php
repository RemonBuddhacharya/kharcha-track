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
        // Prepare scatter plot data: x = date, y = anomaly_score
        $points = collect($this->anomalies)
            ->map(function ($a) {
                return [
                    'x' => $a['reviewed_at'] ? Carbon::parse($a['reviewed_at'])->format('Y-m-d') : now()->format('Y-m-d'),
                    'y' => $a['anomaly_score'],
                ];
            })->toArray();
        $this->anomalyChart = [
            'type' => 'scatter',
            'data' => [
                'datasets' => [[
                    'label' => 'Anomaly Score by Date',
                    'data' => $points,
                    'backgroundColor' => '#f87171',
                    'borderColor' => '#dc2626',
                    'showLine' => false,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Chart.js Scatter Chart',
                    ],
                ],
                'scales' => [
                    'x' => [
                        'type' => 'time',
                        'title' => [
                            'display' => true,
                            'text' => 'Date',
                        ],
                    ],
                    'y' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Anomaly Score',
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
        <div class="font-semibold mb-2">Anomalies Detected (Scatter Plot)</div>
        <x-chart wire:model="anomalyChart" />
    </x-card>
    <x-card>
        <div class="font-semibold mb-2">Anomalies Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expense ID</th>
                        <th>Score</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($anomalies as $row)
                        <tr>
                            <td>{{ $row['reviewed_at'] ? \Illuminate\Support\Carbon::parse($row['reviewed_at'])->format('Y-m-d') : '-' }}</td>
                            <td>{{ $row['expense_id'] }}</td>
                            <td>{{ $row['anomaly_score'] }}</td>
                            <td>{{ $row['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
