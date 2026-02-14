<x-filament-panels::page>
    {{-- Alert table rendered via Table Builder when available, fallback to static HTML --}}
    @if(isset($this) && method_exists($this, 'getTable'))
        {{ $this->table }}
    @else
        <x-filament::section heading="Configured Alerts">
            @if(empty($alerts ?? []))
                <p class="text-gray-500 text-sm">No alerts configured yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Type</th>
                                <th class="text-left p-2">Channels</th>
                                <th class="text-center p-2">Enabled</th>
                                <th class="text-right p-2">Triggers</th>
                                <th class="text-left p-2">Last Triggered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alerts as $alert)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="p-2 font-medium">{{ $alert['name'] ?? 'Unnamed' }}</td>
                                    <td class="p-2">
                                        <span class="px-2 py-1 rounded text-xs bg-primary-100 text-primary-700 dark:bg-primary-800 dark:text-primary-200">
                                            {{ $alert['type'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="p-2">
                                        @foreach(($alert['channels'] ?? []) as $channel)
                                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 mr-1">{{ $channel }}</span>
                                        @endforeach
                                    </td>
                                    <td class="p-2 text-center">
                                        @if($alert['enabled'] ?? false)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-success-100 text-success-700">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-500">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-right">{{ $alert['trigger_count'] ?? 0 }}</td>
                                    <td class="p-2 text-xs text-gray-500">{{ $alert['last_triggered_at'] ?? 'Never' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
