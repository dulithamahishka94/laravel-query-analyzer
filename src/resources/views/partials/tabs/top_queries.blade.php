<div id="tab-top-queries" class="tab-content hidden h-full overflow-y-auto">
    <div class="grid grid-cols-2 gap-4">
        <!-- Slowest Queries -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Slowest Queries</span>
                <select id="slowest-period" onchange="loadTopQueries('slowest')" class="bg-slate-700 border-0 text-xs rounded px-2 py-1 text-slate-300">
                    <option value="hour">Last Hour</option>
                    <option value="day" selected>Last 24h</option>
                    <option value="week">Last 7d</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div id="slowest-queries" class="divide-y divide-slate-700/50">
                    <div class="p-4 text-center text-slate-500 text-sm">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Most Frequent -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Most Frequent Queries</span>
                <select id="frequent-period" onchange="loadTopQueries('most_frequent')" class="bg-slate-700 border-0 text-xs rounded px-2 py-1 text-slate-300">
                    <option value="hour">Last Hour</option>
                    <option value="day" selected>Last 24h</option>
                    <option value="week">Last 7d</option>
                </select>
            </div>
            <div class="card-body p-0">
                <div id="frequent-queries" class="divide-y divide-slate-700/50">
                    <div class="p-4 text-center text-slate-500 text-sm">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>
