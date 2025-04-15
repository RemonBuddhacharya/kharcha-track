<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expense_id',
        'user_id',
        'title',
        'description',
        'amount',
        'date',
        'category_id',
        'payment_method',
        'is_recurring',
        'action',
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
    ];

    /**
     * Get the expense that owns the history.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the category that owns the expense.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    /**
     * Get the user that created the history.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
