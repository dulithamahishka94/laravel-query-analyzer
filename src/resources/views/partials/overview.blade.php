<div class="p-6 border-b border-slate-800 bg-slate-900/30">
    <div class="grid grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="stat-value" id="stat-total"><span class="stat-spinner"></span></div>
                    <div class="stat-label">Total Queries</div>
                </div>
                <div class="p-2 bg-indigo-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-change neutral" id="stat-total-change">
                <span>vs yesterday</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="stat-value text-rose-400" id="stat-slow"><span class="stat-spinner"></span></div>
                    <div class="stat-label">Slow Queries</div>
                </div>
                <div class="p-2 bg-rose-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-change neutral" id="stat-slow-change">
                <span>vs yesterday</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="stat-value" id="stat-avg"><span class="stat-spinner"></span></div>
                    <div class="stat-label">Avg Duration</div>
                </div>
                <div class="p-2 bg-emerald-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-change neutral" id="stat-avg-change">
                <span>vs yesterday</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <div class="stat-value" id="stat-p95"><span class="stat-spinner"></span></div>
                    <div class="stat-label">P95 Duration</div>
                </div>
                <div class="p-2 bg-amber-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-change neutral" id="stat-p95-change">
                <span>vs yesterday</span>
            </div>
        </div>
    </div>
</div>
