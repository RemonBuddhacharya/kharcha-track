<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExpenseHistory;

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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the expense histories for the expense.
     */
    public function histories()
    {
        return $this->hasMany(ExpenseHistory::class);
    }

    /**
     * Get the category that owns the expense.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    /**
     * Track changes to the expense.
     */
    protected static function booted()
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
