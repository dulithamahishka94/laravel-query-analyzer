<header class="bg-slate-900/80 border-b border-slate-800 backdrop-blur-sm z-50 flex-none">
    <div class="px-6 h-14 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-2 rounded-lg shadow-lg shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">Query Lens</h1>
                    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Observability Dashboard</p>
                </div>
            </div>

            <!-- Quick Stats in Header -->
            <div class="hidden lg:flex items-center gap-6 pl-6 border-l border-slate-800">
                <div class="text-center">
                    <div class="text-lg font-bold text-white tabular-nums" id="header-total">-</div>
                    <div class="text-[10px] text-slate-500 uppercase">Queries</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-rose-400 tabular-nums" id="header-slow">-</div>
                    <div class="text-[10px] text-slate-500 uppercase">Slow</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-indigo-400 tabular-nums" id="header-avg">-</div>
                    <div class="text-[10px] text-slate-500 uppercase">Avg</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-amber-400 tabular-nums" id="header-p95">-</div>
                    <div class="text-[10px] text-slate-500 uppercase">P95</div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Period Selector -->
            <select id="period-select" onchange="onPeriodChange()" class="bg-slate-800 border border-slate-700 text-sm rounded-lg px-3 py-1.5 text-slate-300 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="1h">Last Hour</option>
                <option value="24h" selected>Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
            </select>

            <!-- Storage Driver Indicator -->
            <div id="storage-badge" class="px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-400 border border-slate-700">
                <span id="storage-driver">Cache</span>
            </div>

            <!-- Live Indicator -->
            <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-800 rounded-full border border-slate-700">
                <span class="relative flex h-2 w-2">
                    <span class="pulse-live absolute inline-flex h-full w-full rounded-full {{ $isEnabled ? 'bg-emerald-400' : 'bg-slate-500' }} opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $isEnabled ? 'bg-emerald-500' : 'bg-slate-600' }}"></span>
                </span>
                <span class="text-xs font-medium {{ $isEnabled ? 'text-emerald-400' : 'text-slate-500' }}">
                    {{ $isEnabled ? 'Live' : 'Paused' }}
                </span>
            </div>
        </div>
    </div>
</header>
