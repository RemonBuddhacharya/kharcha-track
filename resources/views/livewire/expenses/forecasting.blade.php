<?php

declare(strict_types=1);

use App\Models\Forecast;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public array $forecastData = [];
    public array $forecastChart = [];

    public function mount(): void
    {
        $userId = Auth::id();
        $this->forecastData = Forecast::forecastForUser($userId, 3);
        $labels = array_map(fn($f) => date('M Y', strtotime($f['forecast_date'])), $this->forecastData);
        $data = array_map(fn($f) => $f['predicted_amount'], $this->forecastData);
        $this->forecastChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Forecasted Expenses',
                    'data' => $data,
                    'backgroundColor' => '#38bdf8',
                    'borderColor' => '#0ea5e9',
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
    <x-header title="Expense Forecast" separator />
    <x-card class="mb-6">
        <div class="font-semibold mb-2">Forecasted Expenses (Next 3 Months)</div>
        <x-chart wire:model="forecastChart" />
    </x-card>
    <x-card>
        <div class="font-semibold mb-2">Forecast Table</div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Predicted Amount</th>
                        <th>Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($forecastData as $row)
                        <tr>
                            <td>{{ date('M Y', strtotime($row['forecast_date'])) }}</td>
                            <td>{{ number_format($row['predicted_amount'], 2) }}</td>
                            <td>{{ $row['confidence_score'] * 100 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
