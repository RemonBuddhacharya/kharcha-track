<?php

declare(strict_types=1);

use App\Models\Anomaly;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public array $anomalies = [];
    public array $anomalyChart = [];

    public function mount(): void
    {
        $userId = Auth::id();
        $this->anomalies = Anomaly::detectForUser($userId);
        // Group anomalies by month for chart
        $grouped = collect($this->anomalies)
            ->groupBy(fn($a) => Carbon::parse($a['reviewed_at'] ?? now())->format('M Y'));
        $labels = $grouped->keys()->toArray();
        $data = $grouped->map->count()->values()->toArray();
        $this->anomalyChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Anomalies Detected',
                    'data' => $data,
                    'backgroundColor' => '#f87171',
                    'borderColor' => '#dc2626',
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true],
                ],
            ],
        ];
    }
}; ?>

<div>
    <x-header title="Expense Anomalies" separator />
    <x-card class="mb-6">
        <div class="font-semibold mb-2">Anomalies Detected (Last 12 Months)</div>
        <x-chart wire:model="anomalyChart" />
    </x-card>
    <x-card>
        <div class="font-semibold mb-2">Anomalies Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
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
