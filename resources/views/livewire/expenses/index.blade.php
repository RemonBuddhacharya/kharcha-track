<?php

use App\Models\Expense;
use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Expenses')]
class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;
    
    // Form properties
    public ?int $editing_id = null;
    public string $title = '';
    public string $description = '';
    public float $amount = 0;
    public ?int $category_id = null;
    public string $date = '';
    public ?string $payment_method = '';
    public bool $is_recurring = false;
    public bool $is_anomaly = false;
    
    public function mount()
    {
        $this->date = date('Y-m-d');
    }
    
    public function with(): array
    {
        return [
            'expenses' => $this->expenses(),
            'headers' => $this->headers(),
            'categories' => Category::all()
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'date', 'label' => 'Date', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => true],
            ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ['key' => 'category', 'label' => 'Category', 'sortable' => false],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-1 text-center', 'sortable' => false],
        ];
    }

    public function expenses()
    {
        return Expense::query()
            ->with('category')
            ->where('user_id', auth()->id())
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%'.$this->search.'%')
                      ->orWhere('notes', 'like', '%'.$this->search.'%');
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function edit(Expense $expense): void
    {
        $this->editing_id = $expense->id;
        $this->title = $expense->title;
        $this->description = $expense->description;
        $this->amount = $expense->amount;
        $this->category_id = $expense->category_id;
        $this->date = $expense->date->format('Y-m-d');
        $this->payment_method = $expense->payment_method;
        $this->is_recurring = $expense->is_recurring;
        $this->is_anomaly = $expense->is_anomaly;
        
        $this->drawer = true;
    }

    public function create(): void
    {
        $this->editing_id = null;
        $this->title = '';
        $this->description = '';
        $this->amount = 0;
        $this->category_id = null;
        $this->date = date('Y-m-d');
        $this->payment_method = '';
        $this->is_recurring = false;
        $this->is_anomaly = false;
        
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|gt:0',
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
            'payment_method' => 'nullable|string',
            'is_recurring' => 'boolean',
            'is_anomaly' => 'boolean',
        ]);
        
        if ($this->editing_id) {
            $expense = Expense::findOrFail($this->editing_id);
            $expense->update($data);
            
            $this->success('Expense updated successfully');
        } else {
            // Add user_id to the data array
            $data['user_id'] = auth()->id();
            
            Expense::create($data);
            
            $this->success('Expense created successfully');
        }
        
        $this->drawer = false;
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
        $this->success('Expense deleted successfully');
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Expenses Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Add Expense" class="btn-primary" @click="$wire.create()" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE -->
    <x-card>
        <x-table 
            :headers="$headers" 
            :rows="$expenses" 
            :sort-by="$sortBy" 
            striped 
            with-pagination
            per-page="perPage"
            :per-page-values="[5, 10, 15, 25, 50]"
        >
            @scope('cell_amount', $expense)
                <div class="font-semibold">
                    {{ number_format($expense->amount, 2) }}
                </div>
            @endscope
            
            @scope('cell_category', $expense)
                <x-badge :value="$expense->category->name" class="badge-ghost" />
            @endscope
            
            @scope('cell_date', $expense)
                {{ \Carbon\Carbon::parse($expense->date)->format('Y-m-d') }}
            @endscope
            
            @scope('actions', $expense)
                <div class="flex justify-center gap-1">
                    <x-button icon="o-pencil" class="btn-ghost btn-sm" @click="$wire.edit({{ $expense->id }})" />
                    <x-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        @click="$wire.delete({{ $expense->id }})"
                        wire:confirm.prompt="Are you sure?\nType DELETE to confirm|DELETE" />
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- DRAWER FORM -->
    <x-drawer wire:model="drawer" title="{{ $editing_id ? 'Edit Expense' : 'New Expense' }}" right separator with-close-button>
        <x-form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Title" wire:model="title" placeholder="e.g. Grocery Shopping" required />
                <x-input label="Amount" wire:model="amount" type="number" min="0" step="0.01" placeholder="0.00" required />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-choices 
                    label="Category" 
                    wire:model="category_id" 
                    :options="$categories" 
                    option-value="id"
                    option-label="name"
                    placeholder="Select a category"
                    single
                    searchable
                    clearable
                    required
                />
                <x-datetime label="Date" wire:model="date" icon="o-calendar" required />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="Payment Method" wire:model="payment_method" placeholder="e.g. Cash, Credit Card" />
                <div class="flex flex-col gap-2">
                    <label class="label">Options</label>
                    <div class="flex gap-4">
                        <x-checkbox label="Is Recurring" wire:model="is_recurring" />
                        <x-checkbox label="Is Anomaly" wire:model="is_anomaly" />
                    </div>
                </div>
            </div>
            
            <x-textarea label="Description" wire:model="description" placeholder="Optional details about this expense" />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.drawer = false" />
                <x-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>