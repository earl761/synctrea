<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Metrics Overview -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $metrics = $this->getMetrics();
                @endphp
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="flex items-center mb-2">
                        <x-heroicon-o-arrow-path class="w-6 h-6 text-primary-600 mr-2" />
                        <span class="text-sm font-medium text-gray-600">Total Syncs</span>
                    </div>
                    <div class="text-3xl font-bold text-primary-600">{{ number_format($metrics['total_syncs']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Total synchronizations processed</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="flex items-center mb-2">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 mr-2" />
                        <span class="text-sm font-medium text-gray-600">Successful</span>
                    </div>
                    <div class="text-3xl font-bold text-success-600">{{ number_format($metrics['successful_syncs']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Successfully completed syncs</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="flex items-center mb-2">
                        <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600 mr-2" />
                        <span class="text-sm font-medium text-gray-600">Failed</span>
                    </div>
                    <div class="text-3xl font-bold text-danger-600">{{ number_format($metrics['failed_syncs']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Failed synchronizations</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col items-center">
                    <div class="flex items-center mb-2">
                        <x-heroicon-o-clock class="w-6 h-6 text-warning-600 mr-2" />
                        <span class="text-sm font-medium text-gray-600">Pending</span>
                    </div>
                    <div class="text-3xl font-bold text-warning-600">{{ number_format($metrics['pending_syncs']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Items waiting for sync</div>
                </div>
            </div>
        </x-filament::section>

        <!-- Performance Metrics -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">{{ number_format($metrics['sync_rate'], 1) }}%</div>
                        <div class="text-sm text-gray-600">Sync Success Rate</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600">{{ number_format($metrics['error_rate'], 1) }}%</div>
                        <div class="text-sm text-gray-600">Error Rate</div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">{{ number_format($metrics['average_sync_time'], 1) }}s</div>
                        <div class="text-sm text-gray-600">Avg Sync Time</div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Status Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    <h3 class="text-lg font-semibold">Status Distribution</h3>
                </x-slot>
                
                @php
                    $statusDistribution = $this->getStatusDistribution();
                @endphp
                
                <div class="space-y-3">
                    @foreach($statusDistribution as $status => $count)
                        <div class="flex justify-between items-center">
                            <span class="capitalize">{{ str_replace('_', ' ', $status) }}</span>
                            <span class="font-semibold">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <h3 class="text-lg font-semibold">Syncs by Destination</h3>
                </x-slot>
                
                @php
                    $syncsByDestination = $this->getSyncsByDestination();
                @endphp
                
                <div class="space-y-3">
                    @foreach($syncsByDestination as $destination => $count)
                        <div class="flex justify-between items-center">
                            <span class="capitalize">{{ $destination }}</span>
                            <span class="font-semibold">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Items Needing Attention -->
        <x-filament::section>
            <x-slot name="heading">
                <h3 class="text-lg font-semibold">Items Needing Attention</h3>
            </x-slot>
            
            @php
                $attentionItems = $this->getItemsNeedingAttention();
            @endphp
            
            @if(count($attentionItems) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Product</th>
                                <th class="text-left py-2">Connection Pair</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Last Attempt</th>
                                <th class="text-left py-2">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attentionItems as $item)
                                <tr class="border-b">
                                    <td class="py-2">{{ $item['product_name'] ?? 'N/A' }}</td>
                                    <td class="py-2">{{ $item['connection_pair_name'] ?? 'N/A' }}</td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($item['sync_status'] === 'failed') bg-red-100 text-red-800
                                            @elseif($item['sync_status'] === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($item['sync_status']) }}
                                        </span>
                                    </td>
                                    <td class="py-2">{{ $item['last_sync_attempt'] ? \Carbon\Carbon::parse($item['last_sync_attempt'])->diffForHumans() : 'Never' }}</td>
                                    <td class="py-2 max-w-xs truncate" title="{{ $item['sync_error'] ?? '' }}">{{ $item['sync_error'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600">No items currently need attention.</p>
            @endif
        </x-filament::section>

        <!-- Top Errors -->
        <x-filament::section>
            <x-slot name="heading">
                <h3 class="text-lg font-semibold">Top Sync Errors</h3>
            </x-slot>
            
            @php
                $topErrors = $this->getTopErrors();
            @endphp
            
            @if(count($topErrors) > 0)
                <div class="space-y-3">
                    @foreach($topErrors as $error)
                        <div class="border-l-4 border-red-400 pl-4">
                            <div class="font-medium text-red-800">{{ $error['error'] }}</div>
                            <div class="text-sm text-gray-600">{{ $error['count'] }} occurrences</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No recent errors to display.</p>
            @endif
        </x-filament::section>

        <!-- Queue Health -->
        <x-filament::section>
            <x-slot name="heading">
                <h3 class="text-lg font-semibold">Queue Health</h3>
            </x-slot>
            
            @php
                $queueHealth = $this->getQueueHealth();
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-xl font-bold">{{ $queueHealth['pending_jobs'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Pending Jobs</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold">{{ $queueHealth['failed_jobs'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Failed Jobs</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-green-600">{{ $queueHealth['status'] ?? 'Unknown' }}</div>
                    <div class="text-sm text-gray-600">Queue Status</div>
                </div>
            </div>
        </x-filament::section>

        <!-- Performance by Connection Pair -->
        <x-filament::section>
            <x-slot name="heading">
                <h3 class="text-lg font-semibold">Performance by Connection Pair</h3>
            </x-slot>
            
            @php
                $performanceData = $this->getPerformanceByConnectionPair();
            @endphp
            
            @if(count($performanceData) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Connection Pair</th>
                                <th class="text-left py-2">Total Syncs</th>
                                <th class="text-left py-2">Success Rate</th>
                                <th class="text-left py-2">Avg Time</th>
                                <th class="text-left py-2">Last Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($performanceData as $data)
                                <tr class="border-b">
                                    <td class="py-2">{{ $data['name'] ?? 'N/A' }}</td>
                                    <td class="py-2">{{ number_format($data['total_syncs'] ?? 0) }}</td>
                                    <td class="py-2">{{ number_format($data['success_rate'] ?? 0, 1) }}%</td>
                                    <td class="py-2">{{ number_format($data['avg_time'] ?? 0, 1) }}s</td>
                                    <td class="py-2">{{ $data['last_sync'] ? \Carbon\Carbon::parse($data['last_sync'])->diffForHumans() : 'Never' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600">No performance data available.</p>
            @endif
        </x-filament::section>
    </div>

    <script>
        // Auto-refresh the page every 30 seconds
        setInterval(function() {
            window.location.reload();
        }, 30000);
    </script>
</x-filament-panels::page>