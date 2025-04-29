<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Anomaly extends Model
{
    use HasFactory;

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
     * Find anomalies for a user using a simple threshold method.
     * This is a placeholder for real anomaly detection.
     */
    public static function detectForUser(int $userId, float $threshold = 2.0): array
    {
        $expenses = DB::table('expenses')
            ->where('user_id', $userId)
            ->where('date', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->orderBy('date')
            ->get();
        $amounts = $expenses->pluck('amount')->toArray();
        $mean = count($amounts) ? array_sum($amounts) / count($amounts) : 0;
        $variance = count($amounts) ? array_sum(array_map(fn($a) => pow($a - $mean, 2), $amounts)) / count($amounts) : 0;
        $std = sqrt($variance);
        $anomalies = [];
        foreach ($expenses as $expense) {
            if ($std > 0 && abs($expense->amount - $mean) > $threshold * $std) {
                $anomalies[] = [
                    'expense_id' => $expense->id,
                    'user_id' => $userId,
                    'anomaly_score' => round(abs($expense->amount - $mean) / $std, 2),
                    'detection_method' => 'stddev',
                    'reason' => 'Amount deviates from mean by more than ' . $threshold . ' stddev',
                    'is_reviewed' => false,
                    'is_confirmed_anomaly' => null,
                    'reviewed_at' => null,
                ];
            }
        }
        return $anomalies;
    }
}
