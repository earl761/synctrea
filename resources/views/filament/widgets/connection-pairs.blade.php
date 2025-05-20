<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            @forelse ($connectionPairs as $pair)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1 flex flex-col md:flex-row md:items-center gap-4">
                        <div class="flex-shrink-0 flex flex-col items-center md:items-start">
                            <div class="flex items-center gap-2">
                                <span class="inline-block px-2 py-1 bg-primary-100 text-primary-700 rounded text-xs font-semibold">Supplier</span>
                                <span class="text-base font-bold text-gray-900 dark:text-white">{{ $pair->supplier->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-block px-2 py-1 bg-secondary-100 text-secondary-700 rounded text-xs font-semibold">Destination</span>
                                <span class="text-base font-bold text-gray-900 dark:text-white">{{ $pair->destination->name }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">SKU Prefix: {{ $pair->sku_prefix }}</p>
                        </div>
                    </div>
                    <div class="flex flex-row md:flex-col items-center md:items-end gap-2 md:gap-3">
                        <a href="{{ route('filament.admin.resources.connection-pair-products.index', ['connection_pair_id' => $pair->id]) }}" class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-semibold rounded hover:bg-primary-700 transition">
                            <x-heroicon-o-shopping-cart class="w-4 h-4 mr-1" /> Manage Catalog
                        </a>
                        <a href="{{ route('filament.admin.resources.pricing-rules.index', ['supplier_id' => $pair->supplier_id, 'destination_id' => $pair->destination_id]) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded hover:bg-primary-100 dark:hover:bg-primary-800 transition" title="Edit Pricing Rules">
                            <x-heroicon-o-cog class="w-4 h-4 mr-1" /> Pricing Rules
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500 dark:text-gray-400">
                    No connection pairs found for your company.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 