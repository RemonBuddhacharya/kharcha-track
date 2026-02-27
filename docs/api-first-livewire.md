# API-First Livewire Feasibility (Kharcha Track)

## Summary
Yes — this project can be refactored to an API-first approach where Livewire fetches data from backend API endpoints instead of using Eloquent models directly. It will require adding API controllers/resources and updating Livewire components to call those endpoints (or an internal API client), but the current stack already supports the required pieces (Laravel 12 + Sanctum + Livewire 4).

## Current State (evidence from repo)
- **Framework/stack**: Laravel 12 + Livewire 4 + MaryUI (see `composer.json`).
- **Livewire usage**: inline Livewire components are defined directly inside Blade templates (e.g., `resources/views/livewire/expenses/index.blade.php`).
- **Data access**: Livewire components use Eloquent models directly (`Expense`, `Category`, etc.).
- **API surface**: `routes/api.php` only exposes `/user` via Sanctum; no resource endpoints exist yet.
- **Auth**: Sanctum is installed (`laravel/sanctum` in `composer.json`, `HasApiTokens` in `app/Models/User.php`).

## What “API-first Livewire” means here
Livewire components continue to render server-side HTML, but their data and mutations are performed by calling backend API endpoints (e.g., `GET /api/expenses`, `POST /api/expenses`) instead of Eloquent queries inside the component.

## Recommended Approach
1. **Define API contracts**
   - Create API controllers under `app/Http/Controllers/Api`.
   - Add request validation via Form Requests.
   - Return data via API Resources (`app/Http/Resources`).
2. **Expose REST endpoints** in `routes/api.php` for:
   - Expenses (index, show, store, update, delete)
   - Categories
   - Forecasts
   - Anomalies
3. **Auth strategy**
   - Use Sanctum tokens for API calls.
   - For Livewire (server-side), you can:
     - **Option A (strict API-first)**: issue a token per user and call HTTP endpoints via `Http::withToken(...)`.
     - **Option B (recommended for same app)**: call API controllers internally (no network) using `app()->handle(Request::create(...))`, while still enforcing API validation, resources, and policies. This keeps API-first structure without adding token friction.
4. **Update Livewire components**
   - Replace direct model usage like `Expense::query()` with API client calls.
   - Replace `save()` / `delete()` logic with API calls (`POST`, `PUT`, `DELETE`).
   - Handle pagination and validation errors from API responses.
5. **Consolidate business logic**
   - Put core logic in services or actions (e.g., `app/Actions/ExpenseActions`) so API controllers and Livewire clients stay thin and consistent.

## Example Mapping (Expenses)
- Current: `resources/views/livewire/expenses/index.blade.php`
  - `expenses()` uses `Expense::query()`
  - `save()` uses `Expense::create/update()`
  - `delete()` uses `$expense->delete()`

- API-first (target):
  - `expenses()` calls `GET /api/expenses` (with search/sort/pagination params)
  - `save()` calls `POST /api/expenses` or `PUT /api/expenses/{id}`
  - `delete()` calls `DELETE /api/expenses/{id}`

## Feasibility Notes
- **High**: Stack already includes Sanctum and API routes file.
- **Main work**: designing API endpoints and replacing component model access.
- **Performance**: internal API dispatch or a shared service layer avoids extra HTTP latency.
- **Testing**: add API tests (Pest) and update Livewire tests to assert API-driven behavior.

## Risks / Tradeoffs
- Extra maintenance if business logic diverges between API and Livewire.
- Increased complexity in handling validation errors and pagination from API responses.
- Potential performance overhead if Livewire calls external HTTP for every interaction.

## Minimal Migration Checklist
- [ ] Add `Api/ExpenseController`, `Api/CategoryController`, etc.
- [ ] Add `ExpenseResource`, `CategoryResource`.
- [ ] Add validation Form Requests.
- [ ] Add API routes with Sanctum auth.
- [ ] Create an internal `ApiClient` service for Livewire.
- [ ] Replace Eloquent usage in Livewire components with `ApiClient` calls.
- [ ] Update tests for API endpoints and Livewire behavior.
