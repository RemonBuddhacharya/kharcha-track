# Expense Management with Livewire

This tutorial will guide you through implementing the expense management functionality in the KharchaTrack application using Livewire components.

## Understanding Livewire Components

In the previous tutorial, we created placeholder Livewire components for our application. Now we'll implement the full functionality of the expense management components.

Livewire is Laravel's full-stack framework that makes building dynamic UIs simple and seamless. It uses server-rendered HTML and real-time updates without writing much JavaScript code.

## Creating the Expense Form Component

Let's start by implementing the expense form component for adding and editing expenses.

### Step 1: Update the ExpenseForm Component

Open `app/Http/Livewire/ExpenseForm.php` and update it with the following code:

```php
<?php

namespace App\Http\Livewire;

use App\Models\Expense;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ExpenseForm extends Component
{
    public $expense_id;
    public $title;
    public $description;
    public $amount;
    public $date;
    public $category;
    public $payment_method;
    public $is_recurring = false;
    
    public $categories = [
        'Food',
        'Transportation',
        'Housing',
        'Utilities',
        'Entertainment',
        'Medical',
        'Education',
        'Shopping',
        'Travel',
        'Other'
    ];
    
    public $payment_methods = [
        'Cash',
        'Credit Card',
        'Debit Card',
        'UPI',
        'Bank Transfer',
        'Other'
    ];
    
    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'amount' => 'required|numeric|min:0',
        'date' => 'required|date',
        'category' => 'required|string',
        'payment_method' => 'required|string',
        'is_recurring' => 'boolean'
    ];
    
    public function mount($expense_id = null)
    {
        $this->date = date('Y-m-d');
        
        if ($expense_id) {
            $this->expense_id = $expense_id;
            $expense = Expense::findOrFail($expense_id);
            
            $this->title = $expense->title;
            $this->description = $expense->description;
            $this->amount = $expense->amount;
            $this->date = $expense->date->format('Y-m-d');
            $this->category = $expense->category;
            $this->payment_method = $expense->payment_method;
            $this->is_recurring = $expense->is_recurring;
        }
    }
    
    public function save()
    {
        $this->validate();
        
        $expense = $this->expense_id 
            ? Expense::findOrFail($this->expense_id) 
            : new Expense();
            
        $expense->user_id = Auth::id();
        $expense->title = $this->title;
        $expense->description = $this->description;
        $expense->amount = $this->amount;
        $expense->date = $this->date;
        $expense->category = $this->category;
        $expense->payment_method = $this->payment_method;
        $expense->is_recurring = $this->is_recurring;
        
        $expense->save();
        
        // Only reset if creating a new expense (not when editing)
        if (!$this->expense_id) {
            $this->reset(['title', 'description', 'amount', 'category', 'payment_method', 'is_recurring']);
            $this->date = date('Y-m-d');
        }
        
        $this->emit('expenseSaved');
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => $this->expense_id ? 'Expense updated successfully!' : 'Expense added successfully!'
        ]);
    }
    
    public function render()
    {
        return view('livewire.expense-form');
    }
}
```

### Step 2: Create the ExpenseForm View

Now let's create the view for the ExpenseForm component. Create/update the file `resources/views/livewire/expense-form.blade.php`:

```php
<div>
    <x-mary-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">{{ $expense_id ? 'Edit Expense' : 'Add New Expense' }}</h2>
        </div>

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-mary-input wire:model="title" label="Title" placeholder="Expense title" required />
                    @error('title') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <x-mary-input wire:model="amount" label="Amount" type="number" step="0.01" placeholder="0.00" required />
                    @error('amount') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <x-mary-input wire:model="date" label="Date" type="date" required />
                    @error('date') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <x-mary-select wire:model="category" label="Category" placeholder="Select a category" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </x-mary-select>
                    @error('category') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <x-mary-select wire:model="payment_method" label="Payment Method" placeholder="Select payment method" required>
                        @foreach($payment_methods as $method)
                            <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </x-mary-select>
                    @error('payment_method') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                
                <div>
                    <x-mary-toggle wire:model="is_recurring" label="Recurring Expense" />
                </div>
                
                <div class="col-span-1 md:col-span-2">
                    <x-mary-textarea wire:model="description" label="Description" placeholder="Add notes about this expense" />
                    @error('description') <p class="text-error text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            
            <div class="flex justify-end mt-4">
                <x-mary-button type="submit" color="primary">
                    {{ $expense_id ? 'Update Expense' : 'Add Expense' }}
                </x-mary-button>
            </div>
        </form>
    </x-mary-card>
</div>
```

## Creating the Expense List Component

Next, let's implement the expense list component for displaying and managing expenses.

### Step 1: Update the ExpenseList Component

Open `app/Http/Livewire/ExpenseList.php` and update it with the following code:

```php
<?php

namespace App\Http\Livewire;

use App\Models\Expense;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ExpenseList extends Component
{
    use WithPagination;
    
    public $search = '';
    public $category = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    public $categories = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'date'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    protected $listeners = ['expenseSaved' => '$refresh'];
    
    public function mount()
    {
        $this->categories = Expense::where('user_id', Auth::id())
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function resetFilters()
    {
        $this->reset(['search', 'category', 'dateFrom', 'dateTo']);
    }
    
    public function deleteExpense($id)
    {
        $expense = Expense::findOrFail($id);
        
        if ($expense->user_id !== Auth::id()) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'You are not authorized to delete this expense!'
            ]);
            return;
        }
        
        $expense->delete();
        
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Expense deleted successfully!'
        ]);
    }
    
    public function render()
    {
        $userId = Auth::id();
        
        $query = Expense::where('user_id', $userId);
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->category) {
            $query->where('category', $this->category);
        }
        
        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }
        
        $expenses = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        
        return view('livewire.expense-list', [
            'expenses' => $expenses,
        ]);
    }
}
```

### Step 2: Create the ExpenseList View

Now let's create the view for the ExpenseList component. Create/update the file `resources/views/livewire/expense-list.blade.php`:

```php
<div>
    <x-mary-card>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">My Expenses</h2>
            <x-mary-button link="{{ route('expenses.create') }}" color="primary" icon="o-plus" label="Add Expense" />
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <x-mary-input wire:model.debounce.500ms="search" placeholder="Search expenses..." icon="o-magnifying-glass" />
            </div>
            
            <div>
                <x-mary-select wire:model="category" placeholder="All Categories">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </x-mary-select>
            </div>
            
            <div>
                <x-mary-input wire:model="dateFrom" type="date" placeholder="From Date" />
            </div>
            
            <div>
                <x-mary-input wire:model="dateTo" type="date" placeholder="To Date" />
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <x-mary-table striped>
                <x-slot:header>
                    <x-mary-table-heading sortable wire:click="sortBy('date')" :direction="$sortField === 'date' ? $sortDirection : null">
                        Date
                    </x-mary-table-heading>
                    <x-mary-table-heading sortable wire:click="sortBy('title')" :direction="$sortField === 'title' ? $sortDirection : null">
                        Title
                    </x-mary-table-heading>
                    <x-mary-table-heading sortable wire:click="sortBy('category')" :direction="$sortField === 'category' ? $sortDirection : null">
                        Category
                    </x-mary-table-heading>
                    <x-mary-table-heading sortable wire:click="sortBy('amount')" :direction="$sortField === 'amount' ? $sortDirection : null">
                        Amount
                    </x-mary-table-heading>
                    <x-mary-table-heading>
                        Actions
                    </x-mary-table-heading>
                </x-slot:header>
                
                @forelse($expenses as $expense)
                    <x-mary-table-row :highlight="$expense->is_anomaly">
                        <x-mary-table-cell>
                            {{ $expense->date->format('M d, Y') }}
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <div class="flex items-center">
                                <span>{{ $expense->title }}</span>
                                @if($expense->is_recurring)
                                    <x-mary-badge color="info" size="sm" class="ml-2">Recurring</x-mary-badge>
                                @endif
                                @if($expense->is_anomaly)
                                    <x-mary-badge color="error" size="sm" class="ml-2">Anomaly</x-mary-badge>
                                @endif
                            </div>
                            @if($expense->description)
                                <p class="text-xs text-gray-500">{{ Str::limit($expense->description, 50) }}</p>
                            @endif
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <x-mary-badge color="primary">{{ $expense->category }}</x-mary-badge>
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <span class="font-semibold">{{ number_format($expense->amount, 2) }}</span>
                        </x-mary-table-cell>
                        <x-mary-table-cell>
                            <div class="flex space-x-2">
                                <x-mary-button link="{{ route('expenses.edit', $expense->id) }}" icon="o-pencil" size="sm" color="info" />
                                <x-mary-button wire:click="deleteExpense({{ $expense->id }})" wire:confirm="Are you sure you want to delete this expense?" icon="o-trash" size="sm" color="error" />
                            </div>
                        </x-mary-table-cell>
                    </x-mary-table-row>
                @empty
                    <x-mary-table-row>
                        <x-mary-table-cell colspan="5" class="text-center py-4">
                            <div class="flex flex-col items-center justify-center">
                                <x-mary-icon name="o-exclamation-circle" class="w-12 h-12 text-gray-400" />
                                <p class="mt-2 text-gray-500">No expenses found</p>
                                <x-mary-button link="{{ route('expenses.create') }}" color="primary" class="mt-4">Add Your First Expense</x-mary-button>
                            </div>
                        </x-mary-table-cell>
                    </x-mary-table-row>
                @endforelse
            </x-mary-table>
        </div>
        
        <div class="mt-4">
            {{ $expenses->links() }}
        </div>
        
        <div class="flex justify-between items-center mt-6">
            <x-mary-button wire:click="resetFilters" color="ghost" icon="o-x-mark" label="Clear Filters" />
            
            <div class="flex items-center space-x-2">
                <span class="text-sm">Show</span>
                <x-mary-select wire:model="perPage" class="w-20">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </x-mary-select>
                <span class="text-sm">per page</span>
            </div>
        </div>
    </x-mary-card>
</div>
```

## Implementing the Expense Dashboard Component

Now let's implement the dashboard component that will display expense statistics.

### Step 1: Update the ExpenseDashboard Component

Open `app/Http/Livewire/ExpenseDashboard.php` and update it with the following code:

```php
<?php

namespace App\Http\Livewire;

use App\Models\Expense;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseDashboard extends Component
{
    public $totalExpenses;
    public $monthlySummary;
    public $categorySummary;
    public $recentExpenses;
    public $anomalies;
    public $periodFilter = 'month';
    
    protected $listeners = ['expenseSaved' => '$refresh'];
    
    public function mount()
    {
        $this->updateDashboard();
    }
    
    public function updatePeriodFilter($period)
    {
        $this->periodFilter = $period;
    }
    
    public function updateDashboard()
    {
        $userId = Auth::id();
        
        // Get date range based on filter
        $dateFrom = now();
        
        switch ($this->periodFilter) {
            case 'week':
                $dateFrom = now()->subDays(7);
                break;
            case 'month':
                $dateFrom = now()->subMonth();
                break;
            case 'year':
                $dateFrom = now()->subYear();
                break;
        }
        
        // Total expenses
        $this->totalExpenses = Expense::where('user_id', $userId)
            ->where('date', '>=', $dateFrom)
            ->sum('amount');
            
        // Monthly summary
        $this->monthlySummary = Expense::where('user_id', $userId)
            ->select(DB::raw('DATE_FORMAT(date, "%Y-%m") as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->toArray();
            
        // Category summary
        $this->categorySummary = Expense::where('user_id', $userId)
            ->where('date', '>=', $dateFrom)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
            
        // Recent expenses
        $this->recentExpenses = Expense::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
            
        // Anomalies
        $this->anomalies = Expense::where('user_id', $userId)
            ->where('is_anomaly', true)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();
    }
    
    public function render()
    {
        $this->updateDashboard();
        
        return view('livewire.expense-dashboard');
    }
}
```

### Step 2: Update the ExpenseDashboard View

Create/update the file `resources/views/livewire/expense-dashboard.blade.php` with a more complete implementation:

```php
<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-mary-stat 
            title="Total Expenses" 
            value="{{ number_format($totalExpenses, 2) }}" 
            icon="o-currency-dollar" 
            color="primary"
            desc="Period: {{ ucfirst($periodFilter) }}" />
            
        <x-mary-stat 
            title="Categories" 
            value="{{ count($categorySummary) }}" 
            icon="o-list-bullet" 
            color="accent"
            desc="Different expense categories" />
            
        <x-mary-stat 
            title="Anomalies Detected" 
            value="{{ count($anomalies) }}" 
            icon="o-exclamation-circle" 
            color="{{ count($anomalies) > 0 ? 'error' : 'success' }}"
            desc="{{ count($anomalies) > 0 ? 'Unusual spending detected' : 'No unusual spending detected' }}" />
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-mary-card title="Period Filter">
            <div class="flex justify-center space-x-2">
                <x-mary-button wire:click="updatePeriodFilter('week')" color="{{ $periodFilter === 'week' ? 'primary' : 'ghost' }}">Week</x-mary-button>
                <x-mary-button wire:click="updatePeriodFilter('month')" color="{{ $periodFilter === 'month' ? 'primary' : 'ghost' }}">Month</x-mary-button>
                <x-mary-button wire:click="updatePeriodFilter('year')" color="{{ $periodFilter === 'year' ? 'primary' : 'ghost' }}">Year</x-mary-button>
            </div>
        </x-mary-card>
        
        <x-mary-card title="Quick Actions">
            <div class="flex justify-center space-x-2">
                <x-mary-button link="{{ route('expenses.create') }}" color="primary" icon="o-plus">Add Expense</x-mary-button>
                <x-mary-button link="{{ route('forecasting') }}" color="accent" icon="o-chart-bar">View Forecasts</x-mary-button>
                <x-mary-button link="{{ route('anomalies') }}" color="warning" icon="o-exclamation-circle">View Anomalies</x-mary-button>
            </div>
        </x-mary-card>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-mary-card title="Monthly Summary">
            @if(count($monthlySummary) > 0)
                <div class="chart-container h-64">
                    <canvas id="monthlySummaryChart"></canvas>
                </div>
                
                <script>
                    document.addEventListener('livewire:load', function() {
                        const ctx = document.getElementById('monthlySummaryChart').getContext('2d');
                        const monthlySummary = @json($monthlySummary);
                        
                        const labels = monthlySummary.map(item => item.month);
                        const data = monthlySummary.map(item => item.total);
                        
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Monthly Expenses',
                                    data: data,
                                    backgroundColor: '#3b82f6',
                                    borderColor: '#2563eb',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
                </script>
            @else
                <div class="flex flex-col items-center justify-center h-64">
                    <x-mary-icon name="o-chart-bar" class="w-12 h-12 text-gray-400" />
                    <p class="mt-2 text-gray-500">No monthly data available yet</p>
                </div>
            @endif
        </x-mary-card>
        
        <x-mary-card title="Category Breakdown">
            @if(count($categorySummary) > 0)
                <div class="chart-container h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
                
                <script>
                    document.addEventListener('livewire:load', function() {
                        const ctx = document.getElementById('categoryChart').getContext('2d');
                        const categorySummary = @json($categorySummary);
                        
                        const labels = categorySummary.map(item => item.category);
                        const data = categorySummary.map(item => item.total);
                        
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: [
                                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                                        '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right'
                                    }
                                }
                            }
                        });
                    });
                </script>
            @else
                <div class="flex flex-col items-center justify-center h-64">
                    <x-mary-icon name="o-chart-pie" class="w-12 h-12 text-gray-400" />
                    <p class="mt-2 text-gray-500">No category data available yet</p>
                </div>
            @endif
        </x-mary-card>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-card title="Recent Expenses">
            <div class="overflow-x-auto">
                <x-mary-table striped>
                    <x-slot:header>
                        <x-mary-table-heading>Date</x-mary-table-heading>
                        <x-mary-table-heading>Title</x-mary-table-heading>
                        <x-mary-table-heading>Category</x-mary-table-heading>
                        <x-mary-table-heading>Amount</x-mary-table-heading>
                    </x-slot:header>
                    
                    @forelse($recentExpenses as $expense)
                        <x-mary-table-row :highlight="$expense['is_anomaly']">
                            <x-mary-table-cell>{{ date('M d, Y', strtotime($expense['date'])) }}</x-mary-table-cell>
                            <x-mary-table-cell>{{ $expense['title'] }}</x-mary-table-cell>
                            <x-mary-table-cell>
                                <x-mary-badge color="primary">{{ $expense['category'] }}</x-mary-badge>
                            </x-mary-table-cell>
                            <x-mary-table-cell>{{ number_format($expense['amount'], 2) }}</x-mary-table-cell>
                        </x-mary-table-row>
                    @empty
                        <x-mary-table-row>
                            <x-mary-table-cell colspan="4" class="text-center py-4">
                                <div class="flex flex-col items-center justify-center">
                                    <x-mary-icon name="o-exclamation-circle" class="w-12 h-12 text-gray-400" />
                                    <p class="mt-2 text-gray-500">No recent expenses</p>
                                </div>
                            </x-mary-table-cell>
                        </x-mary-table-row>
                    @endforelse
                </x-mary-table>
            </div>
            
            @if(count($recentExpenses) > 0)
                <div class="mt-4 text-center">
                    <x-mary-button link="{{ route('expenses.index') }}" color="ghost" icon="o-arrow-right" icon-right>
                        View All Expenses
                    </x-mary-button>
                </div>
            @endif
        </x-mary-card>
        
        <x-mary-card title="Detected Anomalies">
            <div class="overflow-x-auto">
                <x-mary-table striped>
                    <x-slot:header>
                        <x-mary-table-heading>Date</x-mary-table-heading>
                        <x-mary-table-heading>Title</x-mary-table-heading>
                        <x-mary-table-heading>Category</x-mary-table-heading>
                        <x-mary-table-heading>Amount</x-mary-table-heading>
                    </x-slot:header>
                    
                    @forelse($anomalies as $anomaly)
                        <x-mary-table-row highlight>
                            <x-mary-table-cell>{{ $anomaly->date->format('M d, Y') }}</x-mary-table-cell>
                            <x-mary-table-cell>{{ $anomaly->title }}</x-mary-table-cell>
                            <x-mary-table-cell>
                                <x-mary-badge color="primary">{{ $anomaly->category }}</x-mary-badge>
                            </x-mary-table-cell>
                            <x-mary-table-cell>{{ number_format($anomaly->amount, 2) }}</x-mary-table-cell>
                        </x-mary-table-row>
                    @empty
                        <x-mary-table-row>
                            <x-mary-table-cell colspan="4" class="text-center py-4">
                                <div class="flex flex-col items-center justify-center">
                                    <x-mary-icon name="o-check-circle" class="w-12 h-12 text-success" />
                                    <p class="mt-2 text-gray-500">No anomalies detected</p>
                                </div>
                            </x-mary-table-cell>
                        </x-mary-table-row>
                    @endforelse
                </x-mary-table>
            </div>
            
            @if(count($anomalies) > 0)
                <div class="mt-4 text-center">
                    <x-mary-button link="{{ route('anomalies') }}" color="ghost" icon="o-arrow-right" icon-right>
                        View All Anomalies
                    </x-mary-button>
                </div>
            @endif
        </x-mary-card>
    </div>
</div>
```

## Setting Up Notifications

For the user notifications to work properly, we need to add a bit of JavaScript to handle the notification events.

### Add Notification Handler in app.js

Update the `resources/js/app.js` file to include notification handling:

```js
import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

// Notification handler
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('notify', event => {
        const options = {
            position: 'bottom-right',
            timer: 3000,
        };
        
        if (event.detail.type === 'success') {
            window.dispatchEvent(new CustomEvent('mary-notify', {
                detail: {
                    type: 'success',
                    content: event.detail.message,
                    ...options
                }
            }));
        } else if (event.detail.type === 'error') {
            window.dispatchEvent(new CustomEvent('mary-notify', {
                detail: {
                    type: 'error',
                    content: event.detail.message,
                    ...options
                }
            }));
        }
    });
});
```

## Testing the Expense Management Features

Now that we have implemented the expense management components, let's test them:

1. Make sure your assets are compiled with the latest JavaScript updates:
```bash
npm run build
```

2. Run the application:
```bash
php artisan serve
```

3. Visit `http://localhost:8000/login` and log in to test:
   - Adding new expenses
   - Viewing the expense list with filtering and sorting
   - Editing expenses
   - Deleting expenses
   - Viewing the dashboard with statistics

## What You've Learned

- How to implement Livewire components for expense management
- How to create forms with validation using Livewire
- How to implement CRUD operations for expenses
- How to create a dynamic table with sorting, filtering and pagination
- How to visualize expense data using Chart.js
- How to set up and use notifications

## Next Steps

In the next tutorial, we'll implement the forecasting system to predict future expenses based on historical data.

[Next Tutorial: Forecasting System →](06-forecasting.md)

[← Back to Index](../README.md)