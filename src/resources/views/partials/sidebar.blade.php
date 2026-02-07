<aside class="w-72 bg-slate-900/50 border-r border-slate-800 flex flex-col flex-none overflow-visible">
    <!-- Filters -->
    <div class="p-4 border-b border-slate-800 filters-section relative z-20">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filters</h3>
            <button onclick="resetFilters()" class="text-xs text-slate-500 hover:text-slate-300">Reset</button>
        </div>
        <div class="space-y-2">
            <select id="type-filter" onchange="applyFilters()" class="w-full bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-2 text-slate-300 cursor-pointer hover:border-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Types</option>
                <option value="select">SELECT</option>
                <option value="insert">INSERT</option>
                <option value="update">UPDATE</option>
                <option value="delete">DELETE</option>
                <option value="cache">CACHE</option>
            </select>
            <select id="issue-filter" onchange="applyFilters()" class="w-full bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-2 text-slate-300 cursor-pointer hover:border-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Issues</option>
                <option value="n+1">N+1 Queries</option>
                <option value="performance">Performance</option>
                <option value="security">Security</option>
            </select>
            <div class="flex gap-2">
                <select id="sort-by" onchange="applyFilters()" class="flex-1 bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-2 text-slate-300 cursor-pointer hover:border-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="timestamp">By Time</option>
                    <option value="time">By Duration</option>
                    <option value="complexity">By Complexity</option>
                </select>
                <select id="sort-order" onchange="applyFilters()" class="w-24 bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-2 text-slate-300 cursor-pointer hover:border-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="desc">Desc</option>
                    <option value="asc">Asc</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Request List Header -->
    <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Requests</h3>
            <p class="text-[10px] text-slate-600 mt-0.5" id="request-count">Loading...</p>
        </div>
        <button onclick="refreshRequests()" class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>
    </div>

    <!-- Request List -->
    <div id="request-list" class="flex-1 overflow-y-auto">
        <div class="p-4 text-center text-slate-500 text-sm">Loading requests...</div>
    </div>

    <!-- Actions -->
    <div class="p-4 border-t border-slate-800 space-y-2">
        <button onclick="resetQueries()" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-rose-500/10 text-rose-400 rounded-lg text-sm font-medium hover:bg-rose-500/20 transition-colors border border-rose-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Clear History
        </button>
        <div class="flex gap-2">
            <button onclick="exportData('json')" class="flex-1 px-3 py-2 bg-slate-800 text-slate-400 rounded-lg text-xs font-medium hover:bg-slate-700 transition-colors">
                Export JSON
            </button>
            <button onclick="exportData('csv')" class="flex-1 px-3 py-2 bg-slate-800 text-slate-400 rounded-lg text-xs font-medium hover:bg-slate-700 transition-colors">
                Export CSV
            </button>
        </div>
    </div>
</aside>
