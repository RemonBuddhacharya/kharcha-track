<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Get a raw SQL expression to extract the year from a date column.
     */
    public static function yearExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            default => "strftime('%Y', {$column})",
        };
    }

    /**
     * Get a raw SQL expression to extract the month from a date column.
     */
    public static function monthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "EXTRACT(MONTH FROM {$column})",
            default => "strftime('%m', {$column})",
        };
    }

    /**
     * Get a raw SQL expression to format a date column as 'YYYY-MM'.
     */
    public static function yearMonthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "strftime('%Y-%m', {$column})",
        };
    }

    /**
     * Get a selectRaw expression for year, month, and SUM(amount) grouping.
     */
    public static function yearMonthSumSelect(string $dateColumn = 'date', string $amountColumn = 'amount'): string
    {
        $year = self::yearExpression($dateColumn);
        $month = self::monthExpression($dateColumn);

        return "{$year} as year, {$month} as month, SUM({$amountColumn}) as total";
    }
}
