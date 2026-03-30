<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="description" content="{{ $metaDescription ?? config('app.name') . ' - ' . config('app.description', 'Laravel Application') }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    @vite('resources/js/app.js')
    @laravelPWA
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">
    @mobile
        {{-- NativePHP Mobile: Top Bar --}}
        <native:top-bar
            title="Kharcha Track"
            :show-navigation-icon="false"
            background-color="#1a1a1a"
            text-color="#ffffff"
        >
            <native:top-bar-action id="profile" label="Profile" icon="user" url="/profile" />
        </native:top-bar>
    @endmobile

    @web
        {{-- Web: Navbar mobile only --}}
        <x-nav sticky class="lg:hidden">
            <x-slot:brand>
                <x-partials.brand />
            </x-slot:brand>
            <x-slot:actions>
                <label for="main-drawer" class="lg:hidden me-3">
                    <x-icon name="o-bars-3" class="cursor-pointer" />
                </label>
            </x-slot:actions>
        </x-nav>
    @endweb

    {{-- MAIN --}}
    <x-main>
        @web
            {{-- Web: Sidebar navigation --}}
            <x-layouts.navigation />
        @endweb

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    @mobile
        {{-- NativePHP Mobile: Bottom Navigation --}}
        <native:bottom-nav :dark="false" label-visibility="labeled">
            <native:bottom-nav-item
                id="dashboard"
                icon="home"
                label="Home"
                url="/dashboard"
                :active="request()->is('dashboard')"
            />
            @role('admin')
                <native:bottom-nav-item
                    id="users"
                    icon="person"
                    label="Users"
                    url="/admin/users"
                    :active="request()->is('admin/users')"
                />
                <native:bottom-nav-item
                    id="roles"
                    icon="group"
                    label="Roles"
                    url="/admin/roles"
                    :active="request()->is('admin/roles')"
                />
                <native:bottom-nav-item
                    id="permissions"
                    icon="connections"
                    label="Permissions"
                    url="/admin/permissions"
                    :active="request()->is('admin/permissions')"
                />
                <native:bottom-nav-item
                    id="categories"
                    icon="tag"
                    label="Category"
                    url="/categories"
                    :active="request()->is('categories')"
                />
            @else
                <native:bottom-nav-item
                    id="expenses"
                    icon="receipt"
                    label="Expenses"
                    url="/expenses"
                    :active="request()->is('expenses*')"
                />
                <native:bottom-nav-item
                    id="categories"
                    icon="tag"
                    label="Category"
                    url="/categories"
                    :active="request()->is('categories')"
                />
                <native:bottom-nav-item
                    id="forecast"
                    icon="trending-up"
                    label="Forecast"
                    url="/forecast"
                    :active="request()->is('forecast')"
                />
                <native:bottom-nav-item
                    id="anomalies"
                    icon="warning"
                    label="Anomaly"
                    url="/anomaly"
                    :active="request()->is('anomaly')"
                />
            @endrole
        </native:bottom-nav>
    @endmobile

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
