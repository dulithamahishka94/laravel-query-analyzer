<x-filament-panels::page>
    {{-- Chart widgets are rendered via getHeaderWidgets() when Filament is installed --}}

    {{-- Top queries table rendered via Table Builder when available, fallback to static HTML --}}
    @if(isset($this) && method_exists($this, 'getTable'))
        {{ $this->table }}
    @else
        <x-filament::section heading="Performance Trends">
            @if(empty($trendsData['labels'] ?? []))
                <p class="text-gray-500 text-sm">No trend data available yet. Data will appear after queries are recorded and aggregated.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Data Points</div>
                        <div class="text-xl font-bold">{{ count($trendsData['labels']) }}</div>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Peak Throughput</div>
                        <div class="text-xl font-bold">{{ max($trendsData['throughput'] ?? [0]) }} queries</div>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Peak P95</div>
                        <div class="text-xl font-bold">{{ max($trendsData['p95'] ?? [0]) }}ms</div>
                    </div>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Top Queries">
            @if(empty($topQueries ?? []))
                <p class="text-gray-500 text-sm">No top queries data available.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">#</th>
                                <th class="text-left p-2">SQL</th>
                                <th class="text-right p-2">Count</th>
                                <th class="text-right p-2">Avg Time (ms)</th>
                                <th class="text-right p-2">Total Time (ms)</th>
                                <th class="text-right p-2">Impact</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topQueries as $index => $query)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="p-2">{{ ($query['rank'] ?? $index + 1) }}</td>
                                    <td class="p-2 max-w-md truncate font-mono text-xs">{{ \Illuminate\Support\Str::limit($query['sql_sample'] ?? '', 80) }}</td>
                                    <td class="p-2 text-right">{{ $query['count'] ?? 0 }}</td>
                                    <td class="p-2 text-right font-mono">{{ round(($query['avg_time'] ?? 0) * 1000, 2) }}</td>
                                    <td class="p-2 text-right font-mono">{{ round(($query['total_time'] ?? 0) * 1000, 2) }}</td>
                                    <td class="p-2 text-right font-mono">{{ round(($query['count'] ?? 0) * ($query['avg_time'] ?? 0) * 1000, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
