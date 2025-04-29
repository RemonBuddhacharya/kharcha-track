<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Forecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'predicted_amount',
        'forecast_date',
        'confidence_score',
        'model_parameters',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_amount' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'model_parameters' => 'array',
    ];

    /**
     * Generate a simple forecast for the next N months for a user.
     * This is a placeholder for real ML/statistical forecasting.
     */
    public static function forecastForUser(int $userId, int $months = 3): array
    {
        $now = Carbon::now();
        $results = [];
        $history = DB::table('expenses')
            ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('date', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        $monthlyTotals = $history->pluck('total')->toArray();
        $avg = count($monthlyTotals) ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;
        for ($i = 1; $i <= $months; $i++) {
            $date = $now->copy()->addMonths($i);
            $results[] = [
                'user_id' => $userId,
                'category' => null,
                'predicted_amount' => round($avg, 2),
                'forecast_date' => $date->toDateString(),
                'confidence_score' => 0.7,
                'model_parameters' => null,
            ];
        }
        return $results;
    }
}
