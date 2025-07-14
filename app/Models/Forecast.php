<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Forecast extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
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
     * Get existing forecasts for a user for the next N months
     */
    public static function getExistingForecasts(int $userId, int $months = 3): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->addMonth()->startOfMonth();
        $endDate = $now->copy()->addMonths($months)->endOfMonth();

        $forecasts = self::where('user_id', $userId)
            ->whereNull('category_id')
            ->whereBetween('forecast_date', [$startDate, $endDate])
            ->orderBy('forecast_date')
            ->get();

        if ($forecasts->count() >= $months) {
            return $forecasts->toArray();
        }

        return [];
    }

    /**
     * Generate a forecast using moving average algorithm for the next N months for a user.
     * Saves the forecast to the database.
     */
    public static function forecastForUser(int $userId, int $months = 3): array
    {
        $now = Carbon::now();
        $results = [];

        // Get the last 12 months of expense data
        $history = DB::table('expenses')
            ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
            ->where('user_id', $userId)
            ->where('date', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthlyTotals = $history->pluck('total')->toArray();

        for ($i = 1; $i <= $months; $i++) {
            $date = $now->copy()->addMonths($i)->startOfMonth();
            $forecastDate = $date->toDateString();
            if (count($monthlyTotals) < 3) {
                $avg = count($monthlyTotals) ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;
                $predictedAmount = round($avg, 2);
                $confidence = 0.5;
                $parameters = ['method' => 'simple_average', 'data_points' => count($monthlyTotals)];
            } else {
                $windowSize = 3;
                $recentMonths = array_slice($monthlyTotals, -$windowSize);
                $movingAvg = array_sum($recentMonths) / count($recentMonths);
                $predictedAmount = round($movingAvg, 2);
                $confidence = min(0.9, 0.6 + (count($monthlyTotals) * 0.02));
                $parameters = [
                    'method' => 'moving_average',
                    'window_size' => $windowSize,
                    'data_points' => count($monthlyTotals),
                ];
                $monthlyTotals[] = $movingAvg;
            }

            // Check if forecast already exists for this user/month
            $existing = self::where('user_id', $userId)
                ->whereNull('category_id')
                ->whereDate('forecast_date', $forecastDate)
                ->first();

            $parametersJson = json_encode($parameters);

            if ($existing) {
                // Only update if value or parameters have changed
                if (
                    $existing->predicted_amount != $predictedAmount ||
                    $existing->confidence_score != $confidence ||
                    $existing->model_parameters != $parametersJson
                ) {
                    $existing->update([
                        'predicted_amount' => $predictedAmount,
                        'confidence_score' => $confidence,
                        'model_parameters' => $parametersJson,
                    ]);
                }
                $results[] = $existing->toArray();
            } else {
                $forecast = self::create([
                    'user_id' => $userId,
                    'category_id' => null,
                    'predicted_amount' => $predictedAmount,
                    'forecast_date' => $forecastDate,
                    'confidence_score' => $confidence,
                    'model_parameters' => $parametersJson,
                ]);
                $results[] = $forecast->toArray();
            }
        }

        return $results;
    }

    /**
     * Generate category-specific forecasts for a user
     */
    public static function forecastByCategory(int $userId, int $months = 3): array
    {
        $now = Carbon::now();
        $results = [];

        // Get all categories for the user
        $categories = DB::table('categories')
            ->where('user_id', $userId)
            ->get();

        foreach ($categories as $category) {
            // Get history for this category
            $history = DB::table('expenses')
                ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
                ->where('user_id', $userId)
                ->where('category_id', $category->id)
                ->where('date', '>=', $now->copy()->subMonths(12)->startOfMonth())
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            $monthlyTotals = $history->pluck('total')->toArray();

            for ($i = 1; $i <= $months; $i++) {
                $date = $now->copy()->addMonths($i)->startOfMonth();
                $forecastDate = $date->toDateString();
                if (count($monthlyTotals) < 3) {
                    $avg = count($monthlyTotals) ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;
                    $predictedAmount = round($avg, 2);
                    $confidence = 0.5;
                    $parameters = ['method' => 'simple_average', 'data_points' => count($monthlyTotals)];
                } else {
                    $windowSize = 3;
                    $recentMonths = array_slice($monthlyTotals, -$windowSize);
                    $movingAvg = array_sum($recentMonths) / count($recentMonths);
                    $predictedAmount = round($movingAvg, 2);
                    $confidence = min(0.9, 0.6 + (count($monthlyTotals) * 0.02));
                    $parameters = [
                        'method' => 'moving_average',
                        'window_size' => $windowSize,
                        'data_points' => count($monthlyTotals),
                    ];
                    $monthlyTotals[] = $movingAvg;
                }

                // Check if forecast already exists for this user/category/month
                $existing = self::where('user_id', $userId)
                    ->where('category_id', $category->id)
                    ->whereDate('forecast_date', $forecastDate)
                    ->first();

                $parametersJson = json_encode($parameters);

                if ($existing) {
                    // Only update if value or parameters have changed
                    if (
                        $existing->predicted_amount != $predictedAmount ||
                        $existing->confidence_score != $confidence ||
                        $existing->model_parameters != $parametersJson
                    ) {
                        $existing->update([
                            'predicted_amount' => $predictedAmount,
                            'confidence_score' => $confidence,
                            'model_parameters' => $parametersJson,
                        ]);
                    }
                    $results[] = $existing->toArray();
                } else {
                    $forecast = self::create([
                        'user_id' => $userId,
                        'category_id' => $category->id,
                        'predicted_amount' => $predictedAmount,
                        'forecast_date' => $forecastDate,
                        'confidence_score' => $confidence,
                        'model_parameters' => $parametersJson,
                    ]);
                    $results[] = $forecast->toArray();
                }
            }
        }

        return $results;
    }
}
