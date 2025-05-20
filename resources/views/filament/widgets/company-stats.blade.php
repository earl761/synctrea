<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            @if ($isAdmin)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $totalCompanies }}</div>
                    <div class="text-sm text-gray-500 mt-1">Total Companies</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $activeSuppliers }}</div>
                    <div class="text-sm text-gray-500 mt-1">Active Suppliers</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $activeDestinations }}</div>
                    <div class="text-sm text-gray-500 mt-1">Active Destinations</div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $activeSuppliers }}</div>
                    <div class="text-sm text-gray-500 mt-1">Active Suppliers</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $activeDestinations }}</div>
                    <div class="text-sm text-gray-500 mt-1">Active Destinations</div>
                </div>
            @endif
        </div>
        @if ($isAdmin)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mt-4">
                <h3 class="text-lg font-bold mb-2">Active Subscriptions per Package</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left py-1 px-2">Package</th>
                            <th class="text-left py-1 px-2">Active Subscriptions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptionsPerPackage as $package)
                            <tr>
                                <td class="py-1 px-2">{{ $package->name }}</td>
                                <td class="py-1 px-2">{{ $package->companies_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget> 