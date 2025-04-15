<?php

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
#[Title('Categories')]
class extends Component {
    use Toast;

    public string $search = '';
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public int $perPage = 10;
    
    // Form properties
    public ?int $editing_id = null;
    public string $name = '';
    public string $color = '#3b82f6'; // Default blue color
    
    public function with(): array
    {
        return [
            'categories' => $this->categories(),
            'headers' => $this->headers(),
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'color', 'label' => 'Color', 'class' => 'w-1', 'sortable' => false],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'expense_count', 'label' => 'Expenses', 'sortable' => false],
            ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-1 text-center', 'sortable' => false],
        ];
    }

    public function categories()
    {
        return Category::query()
            ->where('user_id', auth()->id())
            ->withCount('expenses')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function edit(Category $category): void
    {
        $this->editing_id = $category->id;
        $this->name = $category->name;
        $this->color = $category->color ?? '#3b82f6';
        
        $this->drawer = true;
    }

    public function create(): void
    {
        $this->editing_id = null;
        $this->name = '';
        $this->color = '#3b82f6';
        
        $this->drawer = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $query = Category::where('user_id', auth()->id())
                        ->where('name', $value);
                    
                    if ($this->editing_id) {
                        $query->where('id', '!=', $this->editing_id);
                    }
                    
                    if ($query->exists()) {
                        $fail('You already have a category with this name.');
                    }
                }
            ],
            'color' => 'required|string',
        ]);
        
        $data['user_id'] = auth()->id();
        
        if ($this->editing_id) {
            $category = Category::findOrFail($this->editing_id);
            $category->update($data);
            
            $this->success('Category updated successfully');
        } else {
            Category::create($data);
            
            $this->success('Category created successfully');
        }
        
        $this->drawer = false;
    }

    public function delete(Category $category): void
    {
        // Check if category has expenses
        if ($category->expenses()->count() > 0) {
            $this->error('Cannot delete category with expenses. Please reassign or delete those expenses first.');
            return;
        }
        
        $category->delete();
        $this->success('Category deleted successfully');
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Categories Management" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Add Category" class="btn-primary" @click="$wire.create()" icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE -->
    <x-card>
        <x-table 
            :headers="$headers" 
            :rows="$categories" 
            :sort-by="$sortBy" 
            striped 
            with-pagination
            per-page="perPage"
            :per-page-values="[5, 10, 15, 25, 50]"
        >
            @scope('cell_color', $category)
                <div class="w-6 h-6 rounded-full" style="background-color: {{ $category->color ?? '#3b82f6' }}"></div>
            @endscope
            
            @scope('cell_expense_count', $category)
                <div class="font-semibold">
                    {{ $category->expenses_count }}
                </div>
            @endscope
            
            @scope('actions', $category)
                <div class="flex justify-center gap-1">
                    <x-button icon="o-pencil" class="btn-ghost btn-sm" @click="$wire.edit({{ $category->id }})" />
                    <x-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        @click="$wire.delete({{ $category->id }})"
                        wire:confirm.prompt="Are you sure?\nType DELETE to confirm|DELETE" />
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- DRAWER FORM -->
    <x-drawer wire:model="drawer" title="{{ $editing_id ? 'Edit Category' : 'New Category' }}" right separator with-close-button>
        <x-form wire:submit="save">
            <x-input label="Name" wire:model="name" placeholder="e.g. Groceries" />
            
            <x-colorpicker wire:model="color" label="Color" hint="A nice color" />
            
            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.drawer = false" />
                <x-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>