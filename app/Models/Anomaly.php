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
     * Detect and persist anomalies for a user in a date range.
     */
    public static function detectForUser(int $userId, float $threshold = 2.0, string $dateFrom = null, string $dateTo = null): array
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
        $amounts = $expenses->pluck('amount');
        $mean = $amounts->avg();
        $std = $amounts->count() > 1 ? sqrt($amounts->map(fn($a) => pow($a - $mean, 2))->sum() / ($amounts->count() - 1)) : 0;
        $anomalies = [];
        foreach ($expenses as $expense) {
            if ($std == 0) {
                continue;
            }
            $score = abs($expense->amount - $mean) / $std;
            if ($score >= $threshold) {
                $existing = self::where('expense_id', $expense->id)->first();
                if (!$existing) {
                    self::create([
                        'expense_id' => $expense->id,
                        'user_id' => $userId,
                        'anomaly_score' => $score,
                        'detection_method' => 'stddev',
                        'reason' => 'Amount deviates from mean by ' . number_format($score, 2) . ' std devs',
                        'reviewed_at' => $expense->date ?? $expense->created_at,
                    ]);
                }
            }
        }
        // Return persisted anomalies for this user and date range
        $anomalyQuery = self::query()
            ->whereHas('expense', function ($q) use ($userId, $dateFrom, $dateTo) {
                $q->where('user_id', $userId);
                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            });
        return $anomalyQuery->get()->map(function ($a) {
            return [
                'expense_id' => $a->expense_id,
                'anomaly_score' => $a->anomaly_score,
                'reviewed_at' => $a->reviewed_at,
                'reason' => $a->reason,
            ];
        })->toArray();
    }
    /**
     * Get the expense that this anomaly belongs to.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
