<x-filament-panels::page>
    {{-- Stats overview is rendered via getHeaderWidgets() when Filament is installed --}}

    {{-- Query table rendered via Table Builder when available, fallback to static HTML --}}
    @if(isset($this) && method_exists($this, 'getTable'))
        {{ $this->table }}
    @else
        <x-filament::section heading="Recent Queries">
            @if(empty($recentQueries ?? []))
                <p class="text-gray-500 text-sm">No queries recorded yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">SQL</th>
                                <th class="text-left p-2">Type</th>
                                <th class="text-right p-2">Duration (ms)</th>
                                <th class="text-left p-2">Slow</th>
                                <th class="text-left p-2">Origin</th>
                                <th class="text-left p-2">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentQueries as $query)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="p-2 max-w-md truncate font-mono text-xs">{{ \Illuminate\Support\Str::limit($query['sql'] ?? '', 80) }}</td>
                                    <td class="p-2">
                                        <span class="px-2 py-1 rounded text-xs bg-primary-100 text-primary-700 dark:bg-primary-800 dark:text-primary-200">
                                            {{ $query['analysis']['type'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="p-2 text-right font-mono">{{ round(($query['time'] ?? 0) * 1000, 2) }}</td>
                                    <td class="p-2">
                                        @if($query['analysis']['performance']['is_slow'] ?? false)
                                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                                        @else
                                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                                        @endif
                                    </td>
                                    <td class="p-2 text-xs text-gray-500">{{ $query['origin'] ?? '-' }}</td>
                                    <td class="p-2 text-xs text-gray-500">{{ isset($query['timestamp']) ? \Carbon\Carbon::createFromTimestamp($query['timestamp'])->diffForHumans() : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
