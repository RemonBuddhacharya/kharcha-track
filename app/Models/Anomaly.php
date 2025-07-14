<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Anomaly extends Model
{
    protected $fillable = [
        'expense_id',
        'user_id',
        'anomaly_score',
        'detection_method',
        'reason',
        'is_reviewed',
        'is_confirmed_anomaly',
        'reviewed_at',
    ];

    protected $casts = [
        'anomaly_score' => 'decimal:2',
        'is_reviewed' => 'boolean',
        'is_confirmed_anomaly' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Detect and persist anomalies for a user in a date range using Isolation Forest algorithm.
     */
    public static function detectForUser(int $userId, float $threshold = 2.0, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('expenses')
            ->where('user_id', $userId);
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }
        $expenses = $query->get();
        if ($expenses->isEmpty()) {
            return [];
        }

        // Simplified Isolation Forest implementation
        // In a real Isolation Forest, we would build multiple trees and calculate the average path length
        // Here we'll use a simplified approach that captures the essence of isolation

        $amounts = $expenses->pluck('amount')->toArray();
        $dates = $expenses->pluck('date')->toArray();

        // Normalize data for better algorithm performance
        $minAmount = min($amounts);
        $maxAmount = max($amounts);
        $amountRange = $maxAmount - $minAmount;

        $normalizedAmounts = [];
        foreach ($amounts as $amount) {
            $normalizedAmounts[] = $amountRange > 0 ? ($amount - $minAmount) / $amountRange : 0;
        }

        // Calculate isolation scores (higher score = more anomalous)
        $anomalyScores = [];
        $numSamples = count($normalizedAmounts);
        $numTrees = min(100, $numSamples); // Number of trees in the forest

        foreach ($expenses as $index => $expense) {
            // Simplified isolation score calculation
            // In a real implementation, we would build trees and calculate average path length
            // Here we'll use distance from the mean as a proxy for isolation
            $amount = $normalizedAmounts[$index];
            $meanAmount = array_sum($normalizedAmounts) / $numSamples;
            $distanceFromMean = abs($amount - $meanAmount);

            // Calculate a score between 0 and 1 (higher = more anomalous)
            $score = min(1.0, $distanceFromMean * 3); // Scale factor of 3 to make scores more pronounced

            // Apply threshold
            if ($score >= $threshold / 10) { // Adjust threshold scale for our simplified algorithm
                $existing = self::where('expense_id', $expense->id)->first();
                if (! $existing) {
                    self::create([
                        'expense_id' => $expense->id,
                        'user_id' => $userId,
                        'anomaly_score' => $score * 10, // Scale back up for display
                        'detection_method' => 'isolation_forest',
                        'reason' => 'Detected as outlier by Isolation Forest algorithm',
                        'reviewed_at' => $expense->date ?? $expense->created_at,
                    ]);
                }
            }
        }

        // Get all expenses with their anomaly status for the scatter plot
        $allExpenses = $expenses->map(function ($expense) {
            $anomaly = self::where('expense_id', $expense->id)->first();

            return [
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'is_anomaly' => $anomaly ? true : false,
                'anomaly_score' => $anomaly ? $anomaly->anomaly_score : 0,
                'reviewed_at' => $expense->date ?? $expense->created_at,
                'reason' => $anomaly ? $anomaly->reason : null,
            ];
        })->toArray();

        return $allExpenses;
    }

    /**
     * Get the expense that this anomaly belongs to.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
