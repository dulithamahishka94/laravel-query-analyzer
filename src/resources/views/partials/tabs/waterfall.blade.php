<div id="tab-waterfall" class="tab-content hidden h-full overflow-y-auto">
    <!-- Summary Stats -->
    <div id="waterfall-stats" class="hidden grid grid-cols-4 gap-4 mb-4">
        <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700/50">
            <div class="text-2xl font-bold text-white" id="wf-total-queries">0</div>
            <div class="text-xs text-slate-500 uppercase">Total Queries</div>
        </div>
        <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700/50">
            <div class="text-2xl font-bold text-indigo-400" id="wf-total-time">0ms</div>
            <div class="text-xs text-slate-500 uppercase">Total Time</div>
        </div>
        <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700/50">
            <div class="text-2xl font-bold text-amber-400" id="wf-avg-time">0ms</div>
            <div class="text-xs text-slate-500 uppercase">Avg per Query</div>
        </div>
        <div class="bg-slate-800/50 rounded-lg p-4 border border-slate-700/50">
            <div class="text-2xl font-bold text-rose-400" id="wf-slow-count">0</div>
            <div class="text-xs text-slate-500 uppercase">Slow Queries</div>
        </div>
    </div>

    <!-- Waterfall Chart -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Query Timeline</span>
            <span class="text-xs text-slate-500" id="waterfall-info">Select a request to view timeline</span>
        </div>
        <div class="card-body p-0">
            <div id="waterfall-chart" class="min-h-[300px]">
                <div class="flex items-center justify-center h-64 text-slate-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        <p>Select a request from the sidebar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div id="waterfall-legend" class="hidden mt-4 card">
        <div class="card-header">
            <span class="card-title">Legend</span>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-6">
                <!-- Query Types -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase mb-3">Query Types</h4>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded" style="background: #3b82f6"></div>
                            <span class="text-sm text-slate-300">SELECT</span>
                            <span class="text-xs text-slate-500">- Read operations</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded" style="background: #10b981"></div>
                            <span class="text-sm text-slate-300">INSERT</span>
                            <span class="text-xs text-slate-500">- Create new records</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded" style="background: #f59e0b"></div>
                            <span class="text-sm text-slate-300">UPDATE</span>
                            <span class="text-xs text-slate-500">- Modify existing records</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded" style="background: #ef4444"></div>
                            <span class="text-sm text-slate-300">DELETE</span>
                            <span class="text-xs text-slate-500">- Remove records</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded" style="background: #8b5cf6"></div>
                            <span class="text-sm text-slate-300">OTHER</span>
                            <span class="text-xs text-slate-500">- DDL, transactions, etc.</span>
                        </div>
                    </div>
                </div>
                <!-- Reading the Timeline -->
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase mb-3">Reading the Timeline</h4>
                    <div class="space-y-3 text-sm text-slate-400">
                        <div class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">#</span>
                            <span>Query execution order (sequential number)</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-indigo-400 font-bold">Bar</span>
                            <span>Visual representation of query duration relative to total request time</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-rose-400 font-bold">!</span>
                            <span>Indicates a slow query (exceeds threshold)</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-slate-300 font-bold">Hover</span>
                            <span>Mouse over any row to see the full SQL query</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
