# Database Setup for KharchaTrack

Welcome to the second tutorial in the KharchaTrack series! In this tutorial, we'll set up the database structure for our expense tracking application by creating migrations, models, and seeders.

## Step 1: Create Database Migrations

Migrations allow us to define and version our database schema. Let's create migrations for our tables:

### Users Table Migration

Laravel already provides a migration for the users table. You can find it in `database/migrations/2014_10_12_000000_create_users_table.php`. No changes needed here as we'll use the default structure.

### Create Expenses Table Migration

Run the following command to create a migration for the expenses table:

```bash
php artisan make:migration create_expenses_table
```

Open the newly created migration file in `database/migrations` and update its content:

```php
<?php

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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('category');
            $table->string('payment_method')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_anomaly')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
```

### Create Expense Histories Table Migration

```bash
php artisan make:migration create_expense_histories_table
```

Update the content:

```php
<?php

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
        Schema::create('expense_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('category');
            $table->string('payment_method')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('action')->default('update'); // create, update, delete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_histories');
    }
};
```

### Publish Permission Migrations

Since we're using Spatie's Laravel Permission package, we need to publish its migrations:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## Step 2: Create Models

Now let's create the models for our application:

### User Model

The User model already exists in `app/Models/User.php`. Let's update it:

```bash
php artisan make:model User --force
```

Now edit the User model in `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get all expenses for the user.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all expense histories for the user.
     */
    public function expenseHistories()
    {
        return $this->hasMany(ExpenseHistory::class);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
```

### Expense Model

```bash
php artisan make:model Expense
```

Edit the Expense model in `app/Models/Expense.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'category',
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
     * Track changes to the expense.
     */
    protected static function booted()
    {
        static::created(function ($expense) {
            ExpenseHistory::create([
                'expense_id' => $expense->id,
                'user_id' => $expense->user_id,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'category' => $expense->category,
                'payment_method' => $expense->payment_method,
                'is_recurring' => $expense->is_recurring,
                'action' => 'create',
            ]);
        });

        static::updated(function ($expense) {
            ExpenseHistory::create([
                'expense_id' => $expense->id,
                'user_id' => $expense->user_id,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'category' => $expense->category,
                'payment_method' => $expense->payment_method,
                'is_recurring' => $expense->is_recurring,
                'action' => 'update',
            ]);
        });

        static::deleted(function ($expense) {
            ExpenseHistory::create([
                'expense_id' => $expense->id,
                'user_id' => $expense->user_id,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'date' => $expense->date,
                'category' => $expense->category,
                'payment_method' => $expense->payment_method,
                'is_recurring' => $expense->is_recurring,
                'action' => 'delete',
            ]);
        });
    }
}
```

### ExpenseHistory Model

```bash
php artisan make:model ExpenseHistory
```

Edit the ExpenseHistory model in `app/Models/ExpenseHistory.php`:

```php
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
        'category',
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
     * Get the user that created the history.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## Step 3: Create Database Seeders

Let's create seeders to populate our database with initial data:

### Create Roles and Permissions Seeder

```bash
php artisan make:seeder RolesAndPermissionsSeeder
```

Edit the seeder in `database/seeders/RolesAndPermissionsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Expense permissions
            'manage expenses',
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            
            // User permissions
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Admin permissions
            'view dashboard',
            'view forecasts',
            'view anomalies',
            'export data',
            'view logs',
            'manage settings'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'view dashboard',
            'view forecasts',
            'view anomalies',
            'export data'
        ]);

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());
    }
}
```

### Create Admin User Seeder

```bash
php artisan make:seeder AdminUserSeeder
```

Edit the seeder in `database/seeders/AdminUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@kharchatrack.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@kharchatrack.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('user');
    }
}
```

### Update DatabaseSeeder

Edit the main database seeder in `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
```

## Step 4: Run Migrations and Seeders

Now that we have created our migrations, models, and seeders, let's run them to set up the database:

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed
```

## What You've Learned

In this tutorial, you've learned how to:
- Create database migrations for different tables
- Define Eloquent models with relationships and event listeners
- Create database seeders to populate initial data
- Run migrations and seeders to set up the database

## Next Steps

In the next tutorial, we'll implement authentication and user management features using Laravel Breeze and Spatie Permission.

[← Back to Index](../README.md)