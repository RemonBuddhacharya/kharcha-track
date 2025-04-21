<x-layouts.empty title="Offline">
    <div class="container mx-auto p-4">
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h1 class="card-title text-2xl">You are currently offline</h1>
                <p class="py-4">Please check your internet connection and try again.</p>
                <div class="card-actions justify-end">
                    <button class="btn btn-primary" onclick="window.location.reload()">Retry</button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>