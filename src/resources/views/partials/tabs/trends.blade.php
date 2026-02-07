<div id="tab-trends" class="tab-content h-full overflow-y-auto">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Performance Over Time</span>
            <div class="flex items-center gap-2">
                <button onclick="setGranularity('minute')" class="granularity-btn px-3 py-1 text-xs rounded bg-slate-700 text-slate-400" data-granularity="minute">Minute</button>
                <button onclick="setGranularity('hour')" class="granularity-btn px-3 py-1 text-xs rounded bg-slate-700 text-slate-400" data-granularity="hour">Hourly</button>
                <button onclick="setGranularity('day')" class="granularity-btn px-3 py-1 text-xs rounded bg-slate-700 text-slate-400" data-granularity="day">Daily</button>
            </div>
        </div>
        <div class="card-body">
            <div id="trends-chart-container">
                <div id="trends-chart" style="height: 350px;"></div>
                <div id="trends-empty" class="hidden flex flex-col items-center justify-center h-[350px] text-center">
                    <svg class="w-16 h-16 text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-slate-400 text-sm font-medium">No Performance Data</p>
                    <p class="text-slate-500 text-xs mt-1">Queries will appear here as they are captured</p>
                </div>
            </div>
        </div>
    </div>
</div>
