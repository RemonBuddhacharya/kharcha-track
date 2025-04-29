<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount',
        'date',
        'category_id',
        'payment_method',
        'is_recurring',
        'is_anomaly',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_anomaly' => 'boolean',
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense histories for the expense.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ExpenseHistory::class);
    }

    /**
     * Get the category that owns the expense.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    /**
     * Track changes to the expense.
     */
    protected static function booted(): void
    {
        static::created(function ($expense) {
            $expense->histories()->create([
                'user_id' => $expense->user_id,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'category_id' => $expense->category_id,
                'payment_method' => $expense->payment_method,
                'is_recurring' => $expense->is_recurring,
                'action' => 'create',
            ]);
        });

        static::updated(function ($expense) {
            $expense->histories()->create([
                'user_id' => $expense->user_id,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'category_id' => $expense->category_id,
                'payment_method' => $expense->payment_method,
                'is_recurring' => $expense->is_recurring,
                'action' => 'update',
            ]);
        });
    }
}
