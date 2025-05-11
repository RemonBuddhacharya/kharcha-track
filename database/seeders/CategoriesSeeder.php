<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            ['name' => 'Food', 'color' => '#ef4444'],
            ['name' => 'Transportation', 'color' => '#3b82f6'],
            ['name' => 'Housing', 'color' => '#10b981'],
            ['name' => 'Entertainment', 'color' => '#8b5cf6'],
            ['name' => 'Shopping', 'color' => '#f59e0b'],
            ['name' => 'Utilities', 'color' => '#6366f1'],
            ['name' => 'Healthcare', 'color' => '#ec4899'],
            ['name' => 'Other', 'color' => '#64748b'],
        ];

        // Create default categories for each user
        foreach ($defaultCategories as $category) {
            Category::create([
                'name' => $category['name'],
                'color' => $category['color'],
                'user_id' => 1,
            ]);
        }
    }
}
