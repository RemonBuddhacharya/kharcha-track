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
        // Check if we already have forecasts for this period
        $existingForecasts = self::getExistingForecasts($userId, $months);
        if (!empty($existingForecasts)) {
            return $existingForecasts;
        }

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

        // If we have less than 3 months of data, use simple average
        if (count($monthlyTotals) < 3) {
            $avg = count($monthlyTotals) ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;

            for ($i = 1; $i <= $months; $i++) {
                $date = $now->copy()->addMonths($i)->startOfMonth();
                $forecast = self::create([
                    'user_id' => $userId,
                    'category_id' => null,
                    'predicted_amount' => round($avg, 2),
                    'forecast_date' => $date->toDateString(),
                    'confidence_score' => 0.5, // Lower confidence due to limited data
                    'model_parameters' => json_encode(['method' => 'simple_average', 'data_points' => count($monthlyTotals)]),
                ]);

                $results[] = $forecast->toArray();
            }
        } else {
            // Use moving average with window size of 3
            $windowSize = 3;

            for ($i = 1; $i <= $months; $i++) {
                // Calculate moving average based on the most recent months
                $recentMonths = array_slice($monthlyTotals, -$windowSize);
                $movingAvg = array_sum($recentMonths) / count($recentMonths);

                $date = $now->copy()->addMonths($i)->startOfMonth();
                $confidence = min(0.9, 0.6 + (count($monthlyTotals) * 0.02)); // Confidence increases with more data

                $forecast = self::create([
                    'user_id' => $userId,
                    'category_id' => null,
                    'predicted_amount' => round($movingAvg, 2),
                    'forecast_date' => $date->toDateString(),
                    'confidence_score' => $confidence,
                    'model_parameters' => json_encode([
                        'method' => 'moving_average',
                        'window_size' => $windowSize,
                        'data_points' => count($monthlyTotals)
                    ]),
                ]);

                $results[] = $forecast->toArray();

                // Add the forecast to the monthly totals for the next iteration
                $monthlyTotals[] = $movingAvg;
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
            // Check for existing forecasts for this category
            $existingForecasts = self::where('user_id', $userId)
                ->where('category_id', $category->id)
                ->where('forecast_date', '>=', $now->copy()->addMonth()->startOfMonth())
                ->orderBy('forecast_date')
                ->limit($months)
                ->get();

            if ($existingForecasts->count() >= $months) {
                foreach ($existingForecasts as $forecast) {
                    $results[] = $forecast->toArray();
                }
                continue;
            }

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

            // If we have less than 3 months of data, use simple average
            if (count($monthlyTotals) < 3) {
                $avg = count($monthlyTotals) ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;

                for ($i = 1; $i <= $months; $i++) {
                    $date = $now->copy()->addMonths($i)->startOfMonth();
                    $forecast = self::create([
                        'user_id' => $userId,
                        'category_id' => $category->id,
                        'predicted_amount' => round($avg, 2),
                        'forecast_date' => $date->toDateString(),
                        'confidence_score' => 0.5, // Lower confidence due to limited data
                        'model_parameters' => json_encode(['method' => 'simple_average', 'data_points' => count($monthlyTotals)]),
                    ]);

                    $results[] = $forecast->toArray();
                }
            } else {
                // Use moving average with window size of 3
                $windowSize = 3;

                for ($i = 1; $i <= $months; $i++) {
                    // Calculate moving average based on the most recent months
                    $recentMonths = array_slice($monthlyTotals, -$windowSize);
                    $movingAvg = array_sum($recentMonths) / count($recentMonths);

                    $date = $now->copy()->addMonths($i)->startOfMonth();
                    $confidence = min(0.9, 0.6 + (count($monthlyTotals) * 0.02)); // Confidence increases with more data

                    $forecast = self::create([
                        'user_id' => $userId,
                        'category_id' => $category->id,
                        'predicted_amount' => round($movingAvg, 2),
                        'forecast_date' => $date->toDateString(),
                        'confidence_score' => $confidence,
                        'model_parameters' => json_encode([
                            'method' => 'moving_average',
                            'window_size' => $windowSize,
                            'data_points' => count($monthlyTotals)
                        ]),
                    ]);

                    $results[] = $forecast->toArray();

                    // Add the forecast to the monthly totals for the next iteration
                    $monthlyTotals[] = $movingAvg;
                }
            }
        }

        return $results;
    }
}
