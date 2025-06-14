@extends('layouts.app')

@section('title', 'Sync Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Sync Dashboard</h1>
        <p class="text-gray-600">Monitor and manage product synchronization operations</p>
    </div>

    <!-- Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Successful Syncs</p>
                    <p class="text-2xl font-semibold text-gray-900" id="successful-syncs">{{ $metrics['successful_syncs'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Failed Syncs</p>
                    <p class="text-2xl font-semibold text-gray-900" id="failed-syncs">{{ $metrics['failed_syncs'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Syncs</p>
                    <p class="text-2xl font-semibold text-gray-900" id="pending-syncs">{{ $metrics['pending_syncs'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Sync Rate</p>
                    <p class="text-2xl font-semibold text-gray-900" id="sync-rate">{{ $metrics['sync_rate'] }}/hr</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-4">
            <button onclick="batchSync()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                Batch Sync Pending
            </button>
            <button onclick="retryFailed()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors">
                Retry Failed
            </button>
            <button onclick="resetFailed()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors">
                Reset Failed
            </button>
            <button onclick="clearCache()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                Clear Cache
            </button>
            <button onclick="exportData()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                Export Data
            </button>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Sync Status Distribution</h2>
            <div class="space-y-3">
                @foreach($statusDistribution as $status => $count)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                    <span class="font-semibold text-gray-900">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Items Needing Attention</h2>
            <div class="space-y-3">
                @foreach($attention as $type => $count)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $type) }}</span>
                    <span class="font-semibold {{ $count > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Queue Health -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Queue Health</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600">Pending Jobs</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $queueHealth['pending_jobs'] }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Failed Jobs</p>
                <p class="text-2xl font-semibold {{ $queueHealth['failed_jobs'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $queueHealth['failed_jobs'] }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Oldest Pending</p>
                <p class="text-sm text-gray-900">{{ $queueHealth['oldest_pending'] ? \Carbon\Carbon::parse($queueHealth['oldest_pending'])->diffForHumans() : 'None' }}</p>
            </div>
        </div>
    </div>

    <!-- Performance by Connection Pair -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Performance by Connection Pair</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Synced</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($performanceByPair as $pair)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pair->company_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pair->destination_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($pair->total_items) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ number_format($pair->synced_items) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ number_format($pair->failed_items) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $pair->success_rate }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loading-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">Processing...</h3>
                <p class="text-sm text-gray-600" id="loading-message">Please wait while we process your request.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-refresh metrics every 30 seconds
setInterval(refreshMetrics, 30000);

function refreshMetrics() {
    fetch('/sync/api/metrics')
        .then(response => response.json())
        .then(data => {
            document.getElementById('successful-syncs').textContent = data.successful_syncs;
            document.getElementById('failed-syncs').textContent = data.failed_syncs;
            document.getElementById('pending-syncs').textContent = data.pending_syncs;
            document.getElementById('sync-rate').textContent = data.sync_rate + '/hr';
        })
        .catch(error => console.error('Error refreshing metrics:', error));
}

function showLoading(message = 'Processing...') {
    document.getElementById('loading-message').textContent = message;
    document.getElementById('loading-modal').classList.remove('hidden');
    document.getElementById('loading-modal').classList.add('flex');
}

function hideLoading() {
    document.getElementById('loading-modal').classList.add('hidden');
    document.getElementById('loading-modal').classList.remove('flex');
}

function showAlert(message, type = 'success') {
    const alertClass = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
    const alert = document.createElement('div');
    alert.className = `border-l-4 p-4 mb-4 ${alertClass}`;
    alert.innerHTML = `<p>${message}</p>`;
    
    document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function batchSync() {
    if (!confirm('Start batch sync for pending items?')) return;
    
    showLoading('Starting batch sync...');
    
    fetch('/sync/api/batch-sync', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ limit: 100 })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message);
            setTimeout(refreshMetrics, 2000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Error starting batch sync: ' + error.message, 'error');
    });
}

function retryFailed() {
    if (!confirm('Retry all failed items from the last 24 hours?')) return;
    
    showLoading('Retrying failed items...');
    
    // First get failed items, then retry them
    fetch('/sync/api/failed-items?hours=24&per_page=100')
        .then(response => response.json())
        .then(data => {
            if (data.data.length === 0) {
                hideLoading();
                showAlert('No failed items found to retry.');
                return;
            }
            
            const itemIds = data.data.map(item => item.id);
            
            return fetch('/sync/api/retry-failed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ item_ids: itemIds })
            });
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showAlert(data.message);
                setTimeout(refreshMetrics, 2000);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showAlert('Error retrying failed items: ' + error.message, 'error');
        });
}

function resetFailed() {
    if (!confirm('Reset all failed items to pending status?')) return;
    
    showLoading('Resetting failed items...');
    
    fetch('/sync/api/reset-failed', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ hours: 24 })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message);
            setTimeout(refreshMetrics, 2000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Error resetting failed items: ' + error.message, 'error');
    });
}

function clearCache() {
    if (!confirm('Clear analytics cache?')) return;
    
    showLoading('Clearing cache...');
    
    fetch('/sync/api/clear-cache', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Error clearing cache: ' + error.message, 'error');
    });
}

function exportData() {
    const type = prompt('Export type (failed, pending, all):', 'failed');
    if (!type || !['failed', 'pending', 'all'].includes(type)) {
        showAlert('Invalid export type. Use: failed, pending, or all', 'error');
        return;
    }
    
    const hours = prompt('Hours to look back (default: 24):', '24');
    const url = `/sync/api/export?type=${type}&hours=${hours || 24}`;
    
    window.open(url, '_blank');
}
</script>
@endpush