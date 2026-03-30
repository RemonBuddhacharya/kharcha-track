<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Each user can have unique category names
            $table->unique(['name', 'user_id']);
        });

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

        foreach ($defaultCategories as $category) {
            Category::create([
                'name' => $category['name'],
                'color' => $category['color'],
                'user_id' => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
