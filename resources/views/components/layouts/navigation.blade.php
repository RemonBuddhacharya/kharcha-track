<x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">
    {{-- BRAND --}}
    <x-partials.brand class="px-5 pt-4" />
    <x-menu-separator />
    {{-- MENU --}}
    <x-menu activate-by-route>
        {{-- User --}}
        @if ($user = auth()->user())
            <x-menu-item title="Dashboard" icon="o-home" link="/dashboard" />
            @if ($user->hasVerifiedEmail())
                {{-- Dashboard (requires verified email) --}}
                {{-- Admin only menu items (requires verified email) --}}
                @role('admin')
                    <x-menu-sub title="Administration" icon="o-cog">
                        <x-menu-item title="Users" icon="o-users" link="/admin/users" />
                        <x-menu-item title="Roles" icon="o-user-group" link="/admin/roles" />
                        <x-menu-item title="Permissions" icon="o-key" link="/admin/permissions" />
                    </x-menu-sub>
                    <x-menu-item title="Categories" icon="o-tag" link="/categories" />
                @else
                    {{-- User only menu items (requires verified email) --}}
                    <x-menu-sub title="Expenses" icon="o-building-library">
                        <x-menu-item title="Expenses" icon="o-banknotes" link="/expenses" />
                        <x-menu-item title="Forecast" icon="o-presentation-chart-line" link="/forecast" />
                        <x-menu-item title="Anomalies" icon="o-beaker" link="/anomaly" />
                    </x-menu-sub>
                @endrole
            @else
                {{-- Verification reminder --}}
                <div class="p-4 mt-2 text-sm bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700">
                    <p>Please verify your email to access all features.</p>
                    <a href="{{ route('verification.notice') }}" class="text-blue-600 hover:underline">Verify Now</a>
                </div>
            @endif
            {{-- Profile page (always accessible) --}}
            <x-menu-item title="Profile" icon="o-user" link="/profile" />
        @endif
        <x-menu-separator />
        <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover
            class="-mx-2 !-my-2 rounded">
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <x-theme-toggle class="btn btn-circle btn-ghost btn-sm" />
                    <x-button icon="o-arrow-right-start-on-rectangle" class="btn-circle btn-ghost btn-xs"
                        tooltip-left="Log-out" no-wire-navigate link="/logout" />
                </div>
            </x-slot:actions>
        </x-list-item>
    </x-menu>
</x-slot:sidebar>
