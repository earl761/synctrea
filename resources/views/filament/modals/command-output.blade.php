<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Command</h4>
            <p class="text-sm text-gray-900 dark:text-gray-100 font-mono bg-gray-100 dark:bg-gray-800 p-2 rounded">{{ $record->command }}</p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</h4>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($record->status === 'completed') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                @elseif($record->status === 'failed') bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                @elseif($record->status === 'running') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                @endif">
                {{ ucfirst($record->status) }}
            </span>
        </div>
    </div>
    
    @if($record->arguments)
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Arguments</h4>
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded">
            @foreach($record->arguments as $key => $value)
                <div class="text-sm font-mono">
                    <span class="text-blue-600 dark:text-blue-400">{{ $key }}:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Run</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $record->last_run_at ? $record->last_run_at->format('M j, Y g:i A') : 'Never' }}
        </p>
    </div>
    
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Output</h4>
        <div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto">
            @if($record->last_output)
                <pre class="whitespace-pre-wrap">{{ $record->last_output }}</pre>
            @else
                <p class="text-gray-500">No output available</p>
            @endif
        </div>
    </div>
</div>