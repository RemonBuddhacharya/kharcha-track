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
        <x-native-top-bar
            title="Kharcha Track"
            :show-navigation-icon="false"
            background-color="#1a1a1a"
            text-color="#ffffff"
        >
            <x-native-top-bar-action icon="user" url="/profile" />
        </x-native-top-bar>
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
        <x-native-bottom-nav :dark="false" label-visibility="labeled">
            <x-native-bottom-nav-item
                icon="home"
                label="Home"
                url="/dashboard"
                :selected="request()->is('dashboard')"
            />
            <x-native-bottom-nav-item
                icon="banknotes"
                label="Expenses"
                url="/expenses"
                :selected="request()->is('expenses*')"
            />
            <x-native-bottom-nav-item
                icon="tag"
                label="Categories"
                url="/categories"
                :selected="request()->is('categories')"
            />
            <x-native-bottom-nav-item
                icon="chart-line"
                label="Forecast"
                url="/forecast"
                :selected="request()->is('forecast')"
            />
            <x-native-bottom-nav-item
                icon="beaker"
                label="Anomalies"
                url="/anomaly"
                :selected="request()->is('anomaly')"
            />
        </x-native-bottom-nav>
    @endmobile

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
