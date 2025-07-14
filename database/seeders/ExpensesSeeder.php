<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpensesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expense::factory()->count(1000)->create();
    }
}
