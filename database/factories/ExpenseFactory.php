<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $userId = 2;
        $category = Category::where('user_id', $userId)->inRandomOrder()->first();
        if (!$category) {
            $category = Category::factory()->create(['user_id' => $userId]);
        }

        $expenseTypes = [
            ['title' => 'Grocery Shopping', 'description' => 'Bought groceries from the local market.', 'min' => 500, 'max' => 5000],
            ['title' => 'Vegetable Purchase', 'description' => 'Bought fresh vegetables from the bazaar.', 'min' => 200, 'max' => 1500],
            ['title' => 'Milk Purchase', 'description' => 'Bought milk for the week.', 'min' => 100, 'max' => 700],
            ['title' => 'Mobile Recharge', 'description' => 'Recharged NTC/Ncell mobile balance.', 'min' => 50, 'max' => 1000],
            ['title' => 'Internet Bill', 'description' => 'Paid monthly internet bill.', 'min' => 1000, 'max' => 3000],
            ['title' => 'Electricity Bill', 'description' => 'Paid NEA electricity bill.', 'min' => 500, 'max' => 4000],
            ['title' => 'Water Bill', 'description' => 'Paid Khanepani bill.', 'min' => 200, 'max' => 1000],
            ['title' => 'House Rent', 'description' => 'Paid monthly house rent.', 'min' => 5000, 'max' => 25000],
            ['title' => 'Bus Fare', 'description' => 'Paid for local bus travel.', 'min' => 20, 'max' => 200],
            ['title' => 'Taxi Fare', 'description' => 'Paid for taxi ride.', 'min' => 150, 'max' => 1000],
            ['title' => 'Petrol', 'description' => 'Filled petrol in bike/car.', 'min' => 500, 'max' => 4000],
            ['title' => 'Bike Servicing', 'description' => 'Serviced bike at workshop.', 'min' => 500, 'max' => 3000],
            ['title' => 'Movie Ticket', 'description' => 'Watched a movie at QFX.', 'min' => 250, 'max' => 1000],
            ['title' => 'Dining Out', 'description' => 'Ate at a restaurant.', 'min' => 500, 'max' => 5000],
            ['title' => 'Tea/Coffee', 'description' => 'Had tea or coffee at a cafe.', 'min' => 50, 'max' => 500],
            ['title' => 'Snacks', 'description' => 'Bought snacks from a shop.', 'min' => 50, 'max' => 800],
            ['title' => 'Clothing', 'description' => 'Bought new clothes.', 'min' => 1000, 'max' => 10000],
            ['title' => 'Footwear', 'description' => 'Bought new shoes or sandals.', 'min' => 800, 'max' => 6000],
            ['title' => 'Stationery', 'description' => 'Bought stationery items.', 'min' => 50, 'max' => 1000],
            ['title' => 'Book Purchase', 'description' => 'Bought books for study or leisure.', 'min' => 200, 'max' => 3000],
            ['title' => 'Tuition Fee', 'description' => 'Paid tuition/coaching fee.', 'min' => 1000, 'max' => 10000],
            ['title' => 'Medical Checkup', 'description' => 'Visited doctor for checkup.', 'min' => 500, 'max' => 5000],
            ['title' => 'Medicine', 'description' => 'Bought medicines from pharmacy.', 'min' => 100, 'max' => 2000],
            ['title' => 'Hospital Bill', 'description' => 'Paid hospital charges.', 'min' => 1000, 'max' => 20000],
            ['title' => 'Gift Purchase', 'description' => 'Bought gifts for someone.', 'min' => 300, 'max' => 5000],
            ['title' => 'Festival Shopping', 'description' => 'Bought items for festival.', 'min' => 1000, 'max' => 15000],
            ['title' => 'Charity Donation', 'description' => 'Donated to charity.', 'min' => 100, 'max' => 5000],
            ['title' => 'Travel Expense', 'description' => 'Spent on intercity travel.', 'min' => 1000, 'max' => 20000],
            ['title' => 'Hotel Stay', 'description' => 'Paid for hotel accommodation.', 'min' => 1500, 'max' => 10000],
            ['title' => 'Laundry', 'description' => 'Paid for laundry service.', 'min' => 100, 'max' => 1000],
            ['title' => 'Haircut', 'description' => 'Got a haircut at salon.', 'min' => 100, 'max' => 800],
            ['title' => 'Mobile Accessories', 'description' => 'Bought mobile accessories.', 'min' => 200, 'max' => 3000],
            ['title' => 'Repair/Maintenance', 'description' => 'Paid for home or appliance repair.', 'min' => 500, 'max' => 8000],
        ];

        $expense = $this->faker->randomElement($expenseTypes);

        return [
            'user_id' => $userId,
            'title' => $expense['title'],
            'description' => $expense['description'],
            'amount' => $this->faker->randomFloat(2, $expense['min'], $expense['max']),
            'date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'category_id' => $category->id,
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer', 'other']),
            'is_recurring' => $this->faker->boolean(20),
            'is_anomaly' => $this->faker->boolean(5),
        ];
    }
} 