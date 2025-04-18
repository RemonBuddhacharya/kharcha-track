class Diagram

classDiagram
    class User {
        +id: bigint
        +name: string
        +email: string
        +email_verified_at: timestamp
        +password: string
        +previously_verified: boolean
        +remember_token: string
        +created_at: timestamp
        +updated_at: timestamp
        +expenses()
        +categories()
    }

    class Category {
        +id: bigint
        +user_id: bigint
        +name: string
        +description: text
        +created_at: timestamp
        +updated_at: timestamp
        +user()
        +expenses()
    }

    class Expense {
        +id: bigint
        +user_id: bigint
        +category_id: bigint
        +title: string
        +amount: decimal
        +description: text
        +receipt_path: string
        +date: date
        +created_at: timestamp
        +updated_at: timestamp
        +user()
        +category()
        +histories()
    }

    class ExpenseHistory {
        +id: bigint
        +expense_id: bigint
        +user_id: bigint
        +category_id: bigint
        +title: string
        +amount: decimal
        +description: text
        +receipt_path: string
        +date: date
        +changed_by: string
        +created_at: timestamp
        +updated_at: timestamp
        +expense()
        +category()
        +user()
    }

    class Forecast {
        +id: bigint
        +user_id: bigint
        +prediction: json
        +type: string
        +model: string
        +created_at: timestamp
        +updated_at: timestamp
        +user()
    }

    class Anomaly {
        +id: bigint
        +user_id: bigint
        +expense_id: bigint
        +reason: string
        +score: float
        +created_at: timestamp
        +updated_at: timestamp
        +user()
        +expense()
    }


    User "1" --o "*" Expense : has
    User "1" --o "*" Category : has
    User "1" --o "*" ExpenseHistory : has
    User "1" --o "*" Forecast : has
    User "1" --o "*" Anomaly : has
    Category "1" --o "*" Expense : categorizes
    Category "1" --o "*" ExpenseHistory : categorizes
    Expense "1" --o "*" ExpenseHistory : tracks
    Expense "1" --o "*" Anomaly : detects



object diagram

@startuml
' Styling
skinparam object {
    BackgroundColor #F0F8FF
    ArrowColor #2C3E50
    BorderColor #2C3E50
}

' Database Objects
package "Database" {
    object "user: User" as user {
        id = 1
        name = "Remon Buddhacharya"
        email = "user@example.com"
    }

    object "category1: Category" as category1 {
        id = 1
        name = "Food"
        user_id = 1
        color = "#FF5733"
    }

    object "category2: Category" as category2 {
        id = 2
        name = "Transportation"
        user_id = 1
        color = "#33FF57"
    }

    object "expense1: Expense" as expense1 {
        id = 1
        title = "Lunch"
        amount = 500
        category_id = 1
        user_id = 1
        date = "2025-04-15"
        is_recurring = false
        is_anomaly = true
    }

    object "expense2: Expense" as expense2 {
        id = 2
        title = "Bus fare"
        amount = 100
        category_id = 2
        user_id = 1
        date = "2025-04-15"
        is_recurring = true
        is_anomaly = false
    }

    object "expenseHistory: ExpenseHistory" as history {
        id = 1
        expense_id = 1
        user_id = 1
        category_id = 1
        title = "Lunch"
        amount = 450
        changed_by = "user"
        action = "update"
    }

    object "forecast: Forecast" as forecast {
        id = 1
        user_id = 1
        type = "monthly"
        prediction = "{...}"
    }

    object "anomaly: Anomaly" as anomaly {
        id = 1
        user_id = 1
        expense_id = 1
        reason = "Unusual amount"
        score = 0.85
    }
}

' Application Layer
package "Application Layer" {
    package "Providers" {
        object "AppServiceProvider" as asp
        object "VoltServiceProvider" as vsp
    }

    package "Volt Components" {
        object "DashboardComponent" as dashboard
        object "ExpensesIndexComponent" as expensesIndex
        object "CategoryIndexComponent" as categoryIndex
    }
}

' Relationships
user -- category1 : owns >
user -- category2 : owns >
user -- expense1 : owns >
user -- expense2 : owns >
user -- history : creates >
user -- forecast : has >
user -- anomaly : has >

category1 -- expense1 : contains >
category2 -- expense2 : contains >
category1 -- history : references >

expense1 -- history : tracks >
expense1 -- anomaly : triggers >

' Component Relationships
dashboard ..> user
dashboard ..> expense1
dashboard ..> expense2
dashboard ..> forecast

expensesIndex ..> expense1
expensesIndex ..> expense2
expensesIndex ..> category1
expensesIndex ..> category2

categoryIndex ..> category1
categoryIndex ..> category2

vsp ..> dashboard
vsp ..> expensesIndex
vsp ..> categoryIndex
@enduml



State transition diagram

@startuml
' Styling
skinparam state {
    BackgroundColor #F0F8FF
    BorderColor #2C3E50
    ArrowColor #2C3E50
}

' Main States
[*] --> Unauthenticated
Unauthenticated --> Authenticated : Login/Register
Authenticated --> Unauthenticated : Logout

state Authenticated {
    [*] --> DashboardState

    ' Dashboard State with Stats
    state DashboardState {
        state "View Total Expenses" as TotalExpenses
        state "View Monthly Summary" as MonthlySummary
        state "View Category Distribution" as CategoryDistribution
        state "View Recent Activities" as RecentActivities
        TotalExpenses --> MonthlySummary
        MonthlySummary --> CategoryDistribution
        CategoryDistribution --> RecentActivities
    }

    ' Expense Management States
    state "Expense Management" as ExpenseState {
        [*] --> ExpenseList
        ExpenseList --> ExpenseCreate : Add New
        ExpenseList --> ExpenseView : Select
        ExpenseCreate --> ExpenseList : Save
        ExpenseView --> ExpenseEdit : Modify
        ExpenseEdit --> ExpenseView : Update
        ExpenseView --> ExpenseList : Back
        ExpenseView --> [*] : Delete
    }

    ' Category Management States
    state "Category Management" as CategoryState {
        [*] --> CategoryList
        CategoryList --> CategoryCreate : Add New
        CategoryList --> CategoryView : Select
        CategoryCreate --> CategoryList : Save
        CategoryView --> CategoryEdit : Modify
        CategoryEdit --> CategoryView : Update
        CategoryView --> CategoryList : Back
        CategoryView --> [*] : Delete
    }

    ' ML Feature States
    state "ML Features" as MLState {
        state "Forecasting System" as ForecastState {
            [*] --> CalculateMovingAverage
            CalculateMovingAverage --> GeneratePredictions
            GeneratePredictions --> DisplayForecast
        }

        state "Anomaly Detection" as AnomalyState {
            [*] --> CalculateZScore
            CalculateZScore --> DetectAnomalies
            DetectAnomalies --> FlagExpenses
            FlagExpenses --> NotifyUser
        }
    }

    ' State Transitions
    DashboardState --> ExpenseState : Manage Expenses
    DashboardState --> CategoryState : Manage Categories
    DashboardState --> MLState : View Insights
    ExpenseState --> DashboardState : Return
    CategoryState --> DashboardState : Return
    MLState --> DashboardState : Return
}

' Expense Lifecycle
state "Expense Lifecycle" as ExpenseLifecycle {
    [*] --> Created
    Created --> Updated : Edit
    Updated --> Updated : Edit Again
    Created --> Deleted : Delete
    Updated --> Deleted : Delete
    Deleted --> [*]
    
    state Created {
        [*] --> RecordCreated
        RecordCreated --> HistoryCreated : Create History
        HistoryCreated --> AnomalyChecked : Check Anomaly
    }
    
    state Updated {
        [*] --> RecordUpdated
        RecordUpdated --> HistoryUpdated : Update History
        HistoryUpdated --> AnomalyRechecked : Recheck Anomaly
    }
}
@enduml


Sequence diagram
@startuml
' Styling
skinparam sequence {
    ArrowColor #2C3E50
    LifeLineBackgroundColor #F0F8FF
    ParticipantBackgroundColor #F0F8FF
}

actor User
participant "Browser" as B
participant "Livewire\nComponent" as L
participant "Model" as M
participant "Database" as DB

' Authentication Flow
group Authentication
    User -> B: Visit Login
    B -> L: Mount LoginComponent
    L -> M: Attempt Login
    M -> DB: Verify Credentials
    DB --> M: User Data
    M --> L: Auth Success
    L --> B: Redirect to Dashboard
end

' Dashboard Flow
group Dashboard View
    User -> B: Access Dashboard
    B -> L: Mount DashboardComponent
    activate L
    L -> M: Get Expense Summary
    M -> DB: Fetch Statistics
    DB --> M: Return Data
    M --> L: Process Data
    L -> L: Update Charts
    L --> B: Render Dashboard
    deactivate L
end

' Expense Management
group Expense Operations
    User -> B: Create/Edit Expense
    B -> L: Mount ExpenseComponent
    activate L
    L -> M: Save Expense
    M -> DB: Store Data
    
    ' History Tracking
    M -> M: Generate History
    M -> DB: Store History
    
    ' Anomaly Check
    M -> M: Check for Anomaly
    alt is Anomaly
        M -> DB: Flag as Anomaly
        M -> L: Trigger Alert
    end
    
    DB --> M: Confirmation
    M --> L: Update Complete
    L --> B: Show Success
    deactivate L
end

' Category Management
group Category Operations
    User -> B: Manage Categories
    B -> L: Mount CategoryComponent
    activate L
    L -> M: Get Categories
    M -> DB: Fetch Categories
    DB --> M: Category List
    M --> L: Process List
    L --> B: Display Categories
    deactivate L
end

' ML Features
group ML Processing
    L -> M: Request Forecast
    activate M
    M -> M: Calculate Moving Average
    M -> DB: Store Predictions
    M -> M: Detect Anomalies
    M -> DB: Update Flags
    DB --> M: Confirmation
    M --> L: ML Results
    deactivate M
    L --> B: Update UI
end
@enduml


Activity diagram

@startuml
skinparam ActivityBackgroundColor #F0F8FF
skinparam ActivityBorderColor #2C3E50
skinparam ArrowColor #2C3E50
skinparam ActivityDiamondBackgroundColor #F0F8FF
skinparam ActivityDiamondBorderColor #2C3E50

title KharchaTrack System Flow

|Auth|
start
if (Authenticated?) then (no)
    :Login;
    if (Valid?) then (yes)
        :Access System;
    else (no)
        stop
    endif
else (yes)
endif

|#AntiqueWhite|Dashboard|
:View Stats;
fork
    :Expenses;
fork again
    :Categories;
fork again
    :Anomalies;
end fork

|#LightCyan|Expenses|
:List View;
split
    :Create;
split again
    :Edit;
split again
    :Delete;
end split
:Log History;

|#LightGreen|Categories|
:List View;
split
    :Create;
split again
    :Edit;
split again
    if (Has Expenses?) then (yes)
        :Block Delete;
    else (no)
        :Delete;
    endif
end split

|#Pink|ML Features|
fork
    :Forecast;
    :Show Predictions;
fork again
    :Detect Anomalies;
    if (Found?) then (yes)
        :Alert User;
    endif
end fork

|System|
:Update UI;
:Save State;

|User|
:View/Logout;
stop

legend right
  |= Area |= Function |
  |#White| Core Flow |
  |#AntiqueWhite| Dashboard |
  |#LightCyan| Expenses |
  |#LightGreen| Categories |
  |#Pink| ML Features |
endlegend
@enduml


Refinement of Class
@startuml
' Styling
skinparam class {
    BackgroundColor #F0F8FF
    BorderColor #2C3E50
    ArrowColor #2C3E50
}

' Core Models
package "Core Models" {
    class User {
        +id: bigint
        +name: string
        +email: string
        +password: string
        +email_verified_at: timestamp
        +previously_verified: boolean
        +remember_token: string
        +timestamps()
        +expenses()
        +categories()
        +histories()
    }

    class Category {
        +id: bigint
        +user_id: bigint
        +name: string
        +color: string
        +timestamps()
        +user()
        +expenses()
    }

    class Expense {
        +id: bigint
        +user_id: bigint
        +category_id: bigint
        +title: string
        +amount: decimal
        +date: date
        +is_recurring: boolean
        +is_anomaly: boolean
        +timestamps()
        +user()
        +category()
        +histories()
    }
}

' History & Analytics
package "History & Analytics" {
    class ExpenseHistory {
        +id: bigint
        +expense_id: bigint
        +user_id: bigint
        +category_id: bigint
        +title: string
        +amount: decimal
        +date: date
        +action: string
        +timestamps()
        +expense()
        +user()
        +category()
    }

    class Forecast {
        +id: bigint
        +user_id: bigint
        +type: string
        +prediction: json
        +timestamps()
        +user()
        +calculatePrediction()
    }

    class Anomaly {
        +id: bigint
        +user_id: bigint
        +expense_id: bigint
        +reason: string
        +score: float
        +timestamps()
        +user()
        +expense()
        +detectAnomaly()
    }
}

' Livewire Components
package "Livewire Components" {
    class DashboardComponent {
        +totalExpenses: decimal
        +monthlySummary: array
        +categorySummary: array
        +recentExpenses: array
        +anomalies: array
        +periodFilter: string
        +mount()
        +render()
        +updateDashboard()
    }

    class ExpenseComponent {
        +expenses: array
        +categories: array
        +search: string
        +dateFrom: date
        +dateTo: date
        +sortField: string
        +sortDirection: string
        +mount()
        +render()
        +sortBy()
    }
}

' Relationships
User "1" *-- "*" Category : owns
User "1" *-- "*" Expense : owns
Category "1" *-- "*" Expense : contains
Expense "1" *-- "*" ExpenseHistory : tracks
Expense "1" *-- "*" Anomaly : flags
User "1" *-- "*" Forecast : has

' Component Dependencies
DashboardComponent ..> User
DashboardComponent ..> Expense
DashboardComponent ..> Category
DashboardComponent ..> Anomaly
ExpenseComponent ..> Expense
ExpenseComponent ..> Category
@enduml



Refinement of Object
@startuml
' Styling
skinparam object {
    BackgroundColor #F0F8FF
    BorderColor #2C3E50
    ArrowColor #2C3E50
}

' User Instance
object "user: User" as user {
    id = 1
    name = "Remon Buddhacharya"
    email = "user@example.com"
    email_verified_at = "2024-03-20"
}

' Categories
object "foodCategory: Category" as cat1 {
    id = 1
    user_id = 1
    name = "Food"
    color = "#FF5733"
}

object "transportCategory: Category" as cat2 {
    id = 2
    user_id = 1
    name = "Transport"
    color = "#33FF57"
}

' Expenses
object "lunchExpense: Expense" as exp1 {
    id = 1
    user_id = 1
    category_id = 1
    title = "Lunch"
    amount = 500
    date = "2024-03-20"
    is_recurring = false
    is_anomaly = true
}

' History
object "expenseHistory: ExpenseHistory" as hist {
    id = 1
    expense_id = 1
    user_id = 1
    title = "Lunch"
    amount = 500
    action = "create"
}

' Analytics
object "monthlyForecast: Forecast" as forecast {
    id = 1
    user_id = 1
    type = "monthly"
    prediction = "{amount: 15000}"
}

object "expenseAnomaly: Anomaly" as anomaly {
    id = 1
    expense_id = 1
    user_id = 1
    reason = "Amount exceeds average"
    score = 0.85
}

' Components
object "dashboardComponent: DashboardComponent" as dashboard {
    totalExpenses = 15000
    periodFilter = "month"
    monthlySummary = "[...]"
    categorySummary = "[...]"
}

' Relationships
user -- cat1 : owns >
user -- cat2 : owns >
user -- exp1 : owns >
cat1 -- exp1 : categorizes >
exp1 -- hist : tracks >
exp1 -- anomaly : flags >
user -- forecast : has >

' Component Dependencies
dashboard ..> user : displays
dashboard ..> exp1 : shows
dashboard ..> forecast : presents
dashboard ..> anomaly : alerts
@enduml




Component Diagram

@startuml
' Styling
skinparam {
    ComponentStyle uml2
    ComponentBackgroundColor #F0F8FF
    ComponentBorderColor #2C3E50
    ArrowColor #2C3E50
    PackageBackgroundColor transparent
    PackageBorderColor #666666
}

' Title
title KharchaTrack - Component Architecture

' Client
[Web Browser] as Browser

package "Presentation" {
    package "Volt Views" {
        [landing] as Landing
        [dashboard] as DashView
        [profile] as Profile
        
        package "expenses" {
            [index] as ExpIndex
            [anomalies] as ExpAnomaly
            [forecast] as ExpForecast
        }
        
        package "categories" {
            [index] as CatIndex
        }
    }

    package "Mary UI" {
        [mary-components] as MaryUI
    }

    package "Assets" {
        [chart.js] as ChartJs
        [app.js] as AppJs
    }
}

package "Application" {
    [VoltServiceProvider]
    [Middleware]
}

package "Domain" {
    package "Models" {
        [User]
        [Expense]
        [Category]
        [ExpenseHistory]
        [Forecast]
        [Anomaly]
    }

    package "Services" {
        [ML-Services] as MLServices
    }
}

package "Infrastructure" {
    database "DB" {
        [Tables] as DBTables
    }
    [Laravel Sanctum] as Auth
}

' Key Relationships
Browser --> Landing : HTTP
Browser --> DashView

' View Components
DashView --> MaryUI
ExpIndex --> MaryUI
CatIndex --> MaryUI

' Data Flow
DashView --> Expense
DashView --> Category
DashView --> ChartJs
ExpIndex --> Expense
CatIndex --> Category

' Core Relations
User --> Expense
User --> Category
Expense --> ExpenseHistory
Expense --> Anomaly
Category --> Expense

' Services
VoltServiceProvider --> DashView
MLServices --> Forecast
MLServices --> Anomaly

' Data Layer
Expense --> DBTables
Category --> DBTables
ExpenseHistory --> DBTables
Auth --> User
@enduml


Deployment Diagram
flowchart TB
    subgraph DeveloperLaptop["Developer Laptop"]
        subgraph LaravelApplication["Laravel Application"]
            PHPArtisan["PHP Artisan Serve"]
            Blade["Blade Templates"]
            Livewire["Livewire/Volt Components"]
            Models["Models"]
        end
        subgraph FrontendBuildProcess["Frontend Build Process"]
            YarnDev["Yarn Build"]
            Vite["Vite"]
            Assets["CSS/JS Assets"]
        end
        Cloudflared["Cloudflared"]
        subgraph PostgreSQLDB["PostgreSQL"]
            Tables["Tables"]
        end
    end

    subgraph Cloudflare["Cloudflare"]
        CFTunnel["Cloudflare Tunnel"]
        CFDNS["Cloudflare DNS"]
    end

    subgraph Internet["Internet"]
        Users["End Users"]
    end

    PHPArtisan -->|serves| Blade
    PHPArtisan -->|serves| Livewire
    Livewire -->|uses| Models
    Models -->|connects on port 5432| PostgreSQLDB
    YarnDev -->|runs| Vite
    Vite -->|builds| Assets
    PHPArtisan -->|serves| Assets

    PHPArtisan -.->|exposes on localhost:8000| Cloudflared
    Cloudflared -.->|connects securely| CFTunnel
    CFTunnel -.->|routes traffic| CFDNS
    CFDNS -.->|serves content| Users

    %% Notes
    %% note right of PHPArtisan: Running on port 8000
    %% note right of YarnDev: Watches for asset changes
    %% note right of PostgreSQLDB: Running on port 5432
    %% note right of Cloudflared: Secure tunnel to Cloudflare
    %% note right of CFTunnel: Routes traffic to local server
    %% note left of Users: Access via money.remanbuddhacharya.com.np