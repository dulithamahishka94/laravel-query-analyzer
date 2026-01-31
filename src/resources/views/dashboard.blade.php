<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Query Lens</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        /* Markdown Content Styles */
        .markdown-content ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 0.5em; }
        .markdown-content ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 0.5em; }
        .markdown-content p { margin-bottom: 0.75em; }
        .markdown-content strong { font-weight: 600; color: #818cf8; }
        .markdown-content code { background-color: #1e293b; padding: 0.1em 0.3em; border-radius: 0.2em; font-family: 'JetBrains Mono', monospace; font-size: 0.9em; color: #f472b6; }
        .markdown-content pre { background-color: #0f172a; color: #e2e8f0; padding: 1em; border-radius: 0.5em; overflow-x: auto; margin-bottom: 1em; border: 1px solid #334155; }
        .markdown-content pre code { background-color: transparent; color: inherit; padding: 0; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }

        /* Card styles */
        .card { @apply bg-slate-800/50 rounded-xl border border-slate-700/50 backdrop-blur-sm; }
        .card-header { @apply px-4 py-3 border-b border-slate-700/50 flex items-center justify-between; }
        .card-title { @apply text-xs font-semibold text-slate-400 uppercase tracking-wider; }
        .card-body { @apply p-4; }

        /* Stat card */
        .stat-card { @apply bg-gradient-to-br from-slate-800/80 to-slate-900/80 rounded-xl border border-slate-700/50 p-4 backdrop-blur-sm transition-all hover:border-slate-600/50; }
        .stat-value { @apply text-2xl font-bold text-white tabular-nums; }
        .stat-label { @apply text-xs text-slate-500 uppercase tracking-wider mt-1; }
        .stat-change { @apply text-xs font-medium mt-2 flex items-center gap-1; }
        .stat-change.up { @apply text-emerald-400; }
        .stat-change.down { @apply text-rose-400; }
        .stat-change.neutral { @apply text-slate-500; }

        /* Query card */
        .query-card { @apply bg-slate-800/30 rounded-lg border border-slate-700/30 p-3 cursor-pointer transition-all hover:bg-slate-800/50 hover:border-slate-600/50; }
        .query-card.selected { @apply bg-indigo-900/30 border-indigo-500/50; }

        /* Type badges */
        .badge { @apply px-2 py-0.5 rounded text-xs font-semibold; }
        .badge-select { @apply bg-blue-500/20 text-blue-400 border border-blue-500/30; }
        .badge-insert { @apply bg-emerald-500/20 text-emerald-400 border border-emerald-500/30; }
        .badge-update { @apply bg-amber-500/20 text-amber-400 border border-amber-500/30; }
        .badge-delete { @apply bg-rose-500/20 text-rose-400 border border-rose-500/30; }
        .badge-cache { @apply bg-purple-500/20 text-purple-400 border border-purple-500/30; }

        /* Source badges - App/Vendor */
        .badge-source {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        .badge-app {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.1);
            animation: app-glow 3s ease-in-out infinite;
        }
        .badge-app:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.2) 100%);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }
        @keyframes app-glow {
            0%, 100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.1); }
            50% { box-shadow: 0 0 12px rgba(16, 185, 129, 0.2); }
        }
        .badge-vendor {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2) 0%, rgba(71, 85, 105, 0.15) 100%);
            color: #94a3b8;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }
        .badge-vendor:hover {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.3) 0%, rgba(71, 85, 105, 0.25) 100%);
            border-color: rgba(100, 116, 139, 0.5);
            transform: translateY(-1px);
        }

        /* Issue badges */
        .badge-issue {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .badge-issue:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }
        .badge-n-plus-one {
            background: rgba(168, 85, 247, 0.15);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        .badge-n-plus-one:hover {
            background: rgba(168, 85, 247, 0.25);
            box-shadow: 0 0 8px rgba(168, 85, 247, 0.2);
        }
        .badge-slow-query {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }
        .badge-slow-query:hover {
            background: rgba(244, 63, 94, 0.25);
            box-shadow: 0 0 8px rgba(244, 63, 94, 0.2);
        }
        .badge-security {
            background: rgba(251, 146, 60, 0.15);
            color: #fb923c;
            border: 1px solid rgba(251, 146, 60, 0.3);
        }
        .badge-security:hover {
            background: rgba(251, 146, 60, 0.25);
            box-shadow: 0 0 8px rgba(251, 146, 60, 0.2);
        }
        .badge-performance {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .badge-performance:hover {
            background: rgba(251, 191, 36, 0.25);
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.2);
        }

        /* Performance badges */
        .perf-fast { @apply bg-emerald-500/20 text-emerald-400; }
        .perf-moderate { @apply bg-amber-500/20 text-amber-400; }
        .perf-slow { @apply bg-orange-500/20 text-orange-400; }
        .perf-very_slow { @apply bg-rose-500/20 text-rose-400; }

        /* Pulse animation for live indicator */
        .pulse-live {
            animation: pulse-live 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-live {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Sparkline container */
        .sparkline { height: 30px; }

        /* Tab styles - Modern pill design */
        .tabs-container {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 12px;
        }
        .tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .tab:hover {
            color: #e2e8f0;
            background: rgba(51, 65, 85, 0.5);
        }
        .tab.active {
            color: #ffffff;
            background: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .tab .tab-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Alert item */
        .alert-item { @apply p-3 rounded-lg border border-slate-700/50 bg-slate-800/30; }
        .alert-item.critical { @apply border-l-4 border-l-rose-500; }
        .alert-item.warning { @apply border-l-4 border-l-amber-500; }

        /* Select/Dropdown styling fixes */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        select option {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 8px 12px;
        }
        select:focus {
            outline: none;
            ring: 2px;
            ring-color: #6366f1;
            border-color: #6366f1;
        }

        /* Ensure dropdowns appear above other content */
        .filters-section select,
        .card-header select {
            position: relative;
            z-index: 10;
        }

        /* Fix overflow issues - filters visible, request list scrollable */
        #request-list {
            overflow-y: auto;
            min-height: 0;
        }

        /* Details/Summary styling for collapsible sections */
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary {
            list-style: none;
        }
    </style>
</head>
<body class="text-slate-300 min-h-screen">
    <div id="app" class="flex flex-col h-screen">
        <!-- Header -->
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

        <!-- Main Content -->
        <main class="flex-1 flex min-h-0">
            <!-- Sidebar -->
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

            <!-- Main Panel -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Overview Cards Row -->
                <div class="p-6 border-b border-slate-800 bg-slate-900/30">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="stat-card">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="stat-value" id="stat-total">0</div>
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
                                    <div class="stat-value text-rose-400" id="stat-slow">0</div>
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
                                    <div class="stat-value text-indigo-400" id="stat-avg">0ms</div>
                                    <div class="stat-label">Avg Latency</div>
                                </div>
                                <div class="p-2 bg-indigo-500/10 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div class="stat-value text-amber-400" id="stat-p95">0ms</div>
                                    <div class="stat-label">P95 Latency</div>
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

                <!-- Tabs -->
                <div class="px-6 py-3 bg-slate-900/30 border-b border-slate-800">
                    <div class="tabs-container">
                        <button class="tab active" data-tab="trends" onclick="switchTab('trends')">
                            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                            <span>Trends</span>
                        </button>
                        <button class="tab" data-tab="top-queries" onclick="switchTab('top-queries')">
                            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span>Top Queries</span>
                        </button>
                        <button class="tab" data-tab="queries" onclick="switchTab('queries')">
                            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <span>Query List</span>
                        </button>
                        <button class="tab" data-tab="waterfall" onclick="switchTab('waterfall')">
                            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Waterfall</span>
                        </button>
                        <button class="tab" data-tab="alerts" onclick="switchTab('alerts')">
                            <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span>Alerts</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Trends Tab -->
                    <div id="tab-trends" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">Performance Over Time</span>
                                <div class="flex items-center gap-2">
                                    <button onclick="setGranularity('hour')" class="granularity-btn px-3 py-1 text-xs rounded bg-indigo-500/20 text-indigo-400" data-granularity="hour">Hourly</button>
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

                    <!-- Top Queries Tab -->
                    <div id="tab-top-queries" class="tab-content hidden">
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

                    <!-- Query List Tab -->
                    <div id="tab-queries" class="tab-content hidden">
                        <div class="card">
                            <div class="card-header">
                                <span class="card-title">Query List</span>
                                <span class="text-xs text-slate-500" id="queries-info">Select a request to view queries</span>
                            </div>
                            <div class="card-body p-0">
                                <div id="query-list" class="divide-y divide-slate-700/50 max-h-[600px] overflow-y-auto">
                                    <div class="p-8 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                                        </svg>
                                        <p>Select a request from the sidebar to view its queries</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Waterfall Tab -->
                    <div id="tab-waterfall" class="tab-content hidden">
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

                    <!-- Alerts Tab -->
                    <div id="tab-alerts" class="tab-content hidden">
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Alert Configuration -->
                            <div class="col-span-2 card">
                                <div class="card-header">
                                    <span class="card-title">Alert Configuration</span>
                                    <button onclick="showCreateAlertModal()" class="px-3 py-1.5 bg-indigo-500 text-white text-xs font-medium rounded-lg hover:bg-indigo-600 transition-colors">
                                        + New Alert
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div id="alerts-list" class="divide-y divide-slate-700/50">
                                        <div class="p-4 text-center text-slate-500 text-sm">Loading alerts...</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Alert Logs -->
                            <div class="card">
                                <div class="card-header">
                                    <span class="card-title">Recent Triggers</span>
                                </div>
                                <div class="card-body p-0">
                                    <div id="alert-logs" class="divide-y divide-slate-700/50 max-h-[400px] overflow-y-auto">
                                        <div class="p-4 text-center text-slate-500 text-sm">No recent alerts</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Panel -->
            <div id="detail-panel" class="hidden w-[500px] bg-slate-900 border-l border-slate-800 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Query Details</h3>
                    <button onclick="closeDetails()" class="p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="detail-content" class="flex-1 overflow-y-auto p-4">
                    <!-- Content populated by JS -->
                </div>
            </div>
        </main>
    </div>

    <!-- Create Alert Modal -->
    <div id="alert-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-md mx-4 shadow-2xl">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-700">
                <h3 class="text-sm font-semibold text-white">Create Alert</h3>
                <button type="button" onclick="hideAlertModal()" class="p-1 rounded hover:bg-slate-700 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Error Banner -->
            <div id="alert-form-errors" class="hidden px-4 py-2 bg-rose-500/10 border-b border-rose-500/20">
                <div class="flex items-center gap-2 text-rose-400 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span id="alert-form-error-text">Please fix the errors below</span>
                </div>
            </div>
            <form id="alert-form" onsubmit="createAlert(event)" class="p-4 space-y-4" novalidate>
                <!-- Alert Name -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">
                        Alert Name <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="name" id="alert-name"
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                           placeholder="e.g., Slow Query Alert"
                           minlength="3"
                           maxlength="255"
                           required>
                    <p id="alert-name-error" class="hidden mt-1 text-xs text-rose-400"></p>
                    <p class="mt-1 text-[10px] text-slate-500">3-255 characters, descriptive name for this alert</p>
                </div>

                <!-- Alert Type -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">
                        Alert Type <span class="text-rose-400">*</span>
                    </label>
                    <select name="type" id="alert-type" required
                            onchange="updateThresholdLabel()"
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Select alert type...</option>
                        <option value="slow_query">Slow Query - Triggers when a single query exceeds time threshold</option>
                        <option value="n_plus_one">N+1 Detection - Triggers when N+1 query pattern detected</option>
                        <option value="threshold">Threshold - Triggers when metric exceeds custom threshold</option>
                        <option value="error_rate">Error Rate - Triggers when query has issues/warnings</option>
                    </select>
                    <p id="alert-type-error" class="hidden mt-1 text-xs text-rose-400"></p>
                </div>

                <!-- Threshold -->
                <div id="threshold-container">
                    <label class="block text-xs font-medium text-slate-400 mb-1">
                        <span id="threshold-label">Threshold (seconds)</span> <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="threshold" id="alert-threshold"
                               step="0.01"
                               min="0.01"
                               max="3600"
                               value="1.0"
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 pr-12 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                               required>
                        <span id="threshold-unit" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">sec</span>
                    </div>
                    <p id="alert-threshold-error" class="hidden mt-1 text-xs text-rose-400"></p>
                    <p id="threshold-hint" class="mt-1 text-[10px] text-slate-500">Query execution time threshold (0.01 - 3600 seconds)</p>
                </div>

                <!-- Notification Channels -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">
                        Notification Channels <span class="text-rose-400">*</span>
                    </label>
                    <div class="flex flex-wrap gap-4 mt-2">
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                            <input type="checkbox" name="channels[]" value="log" checked
                                   class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Log
                            </span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                            <input type="checkbox" name="channels[]" value="mail"
                                   class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Email
                            </span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                            <input type="checkbox" name="channels[]" value="slack"
                                   class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                                </svg>
                                Slack
                            </span>
                        </label>
                    </div>
                    <p id="alert-channels-error" class="hidden mt-1 text-xs text-rose-400"></p>
                    <p class="mt-1 text-[10px] text-slate-500">Select at least one notification channel</p>
                </div>

                <!-- Cooldown -->
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">
                        Cooldown (minutes) <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="cooldown_minutes" id="alert-cooldown"
                               value="5"
                               min="1"
                               max="1440"
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 pr-12 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                               required>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">min</span>
                    </div>
                    <p id="alert-cooldown-error" class="hidden mt-1 text-xs text-rose-400"></p>
                    <p class="mt-1 text-[10px] text-slate-500">Minimum time between consecutive triggers (1-1440 minutes)</p>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-700">
                    <button type="button" onclick="hideAlertModal()" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="alert-submit-btn"
                            class="px-4 py-2 bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span id="alert-submit-text">Create Alert</span>
                        <svg id="alert-submit-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ==================== State ====================
        const state = {
            currentRequestId: null,
            currentQueries: [],
            currentTab: 'trends',
            granularity: 'hour',
            pollInterval: {{ config('query-lens.dashboard.poll_interval', 5000) }},
            lastPollTimestamp: 0,
            charts: {
                trends: null,
                waterfall: null
            }
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // ==================== Initialization ====================
        document.addEventListener('DOMContentLoaded', async () => {
            await Promise.all([
                loadStorageInfo(),
                loadOverviewStats(),
                loadTrendsChart(),
                refreshRequests()
            ]);
            startPolling();
        });

        // ==================== Polling ====================
        let lastTrendsUpdate = 0;
        let lastTopQueriesUpdate = 0;
        const TRENDS_UPDATE_INTERVAL = 15000; // Update trends every 15 seconds
        const TOP_QUERIES_UPDATE_INTERVAL = 30000; // Update top queries every 30 seconds

        function startPolling() {
            setInterval(async () => {
                try {
                    const res = await fetch(`/query-lens/api/v2/poll?since=${state.lastPollTimestamp}&_cb=${Date.now()}`);
                    const data = await res.json();
                    const now = Date.now();

                    if (data.new_queries && data.new_queries.length > 0) {
                        refreshRequests();
                        if (state.currentRequestId) {
                            const hasNewForRequest = data.new_queries.some(q => q.request_id === state.currentRequestId);
                            if (hasNewForRequest) {
                                loadQueriesForRequest(state.currentRequestId);
                            }
                        }

                        // Update trends chart periodically when new queries arrive
                        if (now - lastTrendsUpdate > TRENDS_UPDATE_INTERVAL) {
                            loadTrendsChart();
                            lastTrendsUpdate = now;
                        }

                        // Update top queries periodically
                        if (now - lastTopQueriesUpdate > TOP_QUERIES_UPDATE_INTERVAL) {
                            loadTopQueries('slowest');
                            loadTopQueries('most_frequent');
                            lastTopQueriesUpdate = now;
                        }
                    }

                    updateHeaderStats(data.stats);
                    state.lastPollTimestamp = data.timestamp;

                    if (data.alerts && data.alerts.length > 0) {
                        // Could show toast notification for new alerts
                    }
                } catch (e) {
                    console.error('Poll error:', e);
                }
            }, state.pollInterval);
        }

        // ==================== Storage Info ====================
        async function loadStorageInfo() {
            try {
                const res = await fetch('/query-lens/api/v2/storage');
                const data = await res.json();
                document.getElementById('storage-driver').textContent =
                    data.driver === 'database' ? 'Database' : 'Cache';

                if (data.supports_persistence) {
                    document.getElementById('storage-badge').classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/30');
                    document.getElementById('storage-badge').classList.remove('bg-slate-800', 'text-slate-400', 'border-slate-700');
                }
            } catch (e) {
                console.error('Error loading storage info:', e);
            }
        }

        // ==================== Overview Stats ====================
        async function loadOverviewStats() {
            try {
                const res = await fetch('/query-lens/api/v2/stats/overview');
                const data = await res.json();

                const today = data.today || {};
                const comparison = data.comparison || {};

                document.getElementById('stat-total').textContent = formatNumber(today.total_queries || 0);
                document.getElementById('stat-slow').textContent = formatNumber(today.slow_queries || 0);
                document.getElementById('stat-avg').textContent = formatMs(today.avg_time || 0);
                document.getElementById('stat-p95').textContent = formatMs(today.p95_time || 0);

                updateStatChange('stat-total-change', comparison.queries);
                updateStatChange('stat-slow-change', comparison.slow);
                updateStatChange('stat-avg-change', comparison.avg_time, true);
            } catch (e) {
                console.error('Error loading overview:', e);
            }
        }

        function updateStatChange(elementId, change, invertGood = false) {
            const el = document.getElementById(elementId);
            if (!change) return;

            const icon = change.direction === 'up' ? '&uarr;' : change.direction === 'down' ? '&darr;' : '';
            const isGood = invertGood ? change.direction === 'down' : change.direction !== 'up';

            el.innerHTML = `<span>${icon} ${change.value}%</span> <span class="text-slate-500">vs yesterday</span>`;
            el.className = `stat-change ${isGood ? (change.direction === 'neutral' ? 'neutral' : 'down') : 'up'}`;
        }

        function updateHeaderStats(stats) {
            if (!stats) return;
            document.getElementById('header-total').textContent = formatNumber(stats.total_queries || 0);
            document.getElementById('header-slow').textContent = formatNumber(stats.slow_queries || 0);
            document.getElementById('header-avg').textContent = formatMs(stats.average_time || 0);
            // P95 would need to be in stats
        }

        // ==================== Trends Chart ====================
        async function loadTrendsChart() {
            try {
                const period = getPeriodDates();
                const res = await fetch(`/query-lens/api/v2/trends?start=${period.start}&end=${period.end}&granularity=${state.granularity}`);
                const data = await res.json();

                renderTrendsChart(data);
            } catch (e) {
                console.error('Error loading trends:', e);
            }
        }

        function renderTrendsChart(data) {
            const chartEl = document.getElementById('trends-chart');
            const emptyEl = document.getElementById('trends-empty');
            const hasData = data.labels && data.labels.length > 0;

            // Toggle visibility
            chartEl.classList.toggle('hidden', !hasData);
            emptyEl.classList.toggle('hidden', hasData);

            if (!hasData) {
                // Destroy existing chart if no data
                if (state.charts.trends) {
                    state.charts.trends.destroy();
                    state.charts.trends = null;
                }
                return;
            }

            const options = {
                series: [
                    { name: 'P50 Latency', data: data.p50 || [] },
                    { name: 'P95 Latency', data: data.p95 || [] },
                    { name: 'P99 Latency', data: data.p99 || [] }
                ],
                chart: {
                    type: 'line',
                    height: 350,
                    background: 'transparent',
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout' }
                },
                colors: ['#818cf8', '#f59e0b', '#ef4444'],
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: data.labels || [],
                    labels: { style: { colors: '#64748b' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#64748b' },
                        formatter: v => v ? v.toFixed(1) + 'ms' : '0ms'
                    }
                },
                grid: { borderColor: '#334155', strokeDashArray: 4 },
                legend: { labels: { colors: '#94a3b8' } },
                tooltip: {
                    theme: 'dark',
                    y: { formatter: v => v ? v.toFixed(2) + 'ms' : '0ms' }
                },
                noData: {
                    text: 'No data available',
                    style: { color: '#64748b', fontSize: '14px' }
                }
            };

            if (state.charts.trends) {
                state.charts.trends.updateOptions(options);
            } else {
                state.charts.trends = new ApexCharts(chartEl, options);
                state.charts.trends.render();
            }
        }

        function setGranularity(g) {
            state.granularity = g;
            document.querySelectorAll('.granularity-btn').forEach(btn => {
                btn.className = btn.dataset.granularity === g
                    ? 'granularity-btn px-3 py-1 text-xs rounded bg-indigo-500/20 text-indigo-400'
                    : 'granularity-btn px-3 py-1 text-xs rounded bg-slate-700 text-slate-400';
            });
            loadTrendsChart();
        }

        // ==================== Top Queries ====================
        async function loadTopQueries(type) {
            const periodEl = type === 'slowest' ? 'slowest-period' : 'frequent-period';
            const period = document.getElementById(periodEl).value;
            const containerId = type === 'slowest' ? 'slowest-queries' : 'frequent-queries';

            try {
                const res = await fetch(`/query-lens/api/v2/top-queries?type=${type}&period=${period}&limit=10`);
                const data = await res.json();

                renderTopQueries(containerId, data.queries || [], type);
            } catch (e) {
                console.error('Error loading top queries:', e);
            }
        }

        function renderTopQueries(containerId, queries, type) {
            const container = document.getElementById(containerId);

            if (!queries.length) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No data available</div>';
                return;
            }

            container.innerHTML = queries.map((q, i) => `
                <div class="p-3 hover:bg-slate-800/50 cursor-pointer" onclick="showTopQueryDetail('${escapeHtml(q.sql_sample)}')">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <span class="text-xs font-mono text-slate-500">#${i + 1}</span>
                        <div class="flex items-center gap-2">
                            ${type === 'slowest'
                                ? `<span class="text-xs font-medium text-amber-400">${(q.avg_time * 1000).toFixed(1)}ms avg</span>`
                                : `<span class="text-xs font-medium text-indigo-400">${formatNumber(q.count)} calls</span>`
                            }
                        </div>
                    </div>
                    <div class="font-mono text-xs text-slate-400 truncate">${escapeHtml(q.sql_sample.substring(0, 100))}</div>
                    <div class="flex items-center gap-3 mt-1 text-[10px] text-slate-500">
                        <span>Total: ${(q.total_time * 1000).toFixed(0)}ms</span>
                        <span>Max: ${(q.max_time * 1000).toFixed(1)}ms</span>
                        ${q.issue_count > 0 ? `<span class="text-rose-400">${q.issue_count} issues</span>` : ''}
                    </div>
                </div>
            `).join('');
        }

        // ==================== Requests ====================
        async function refreshRequests() {
            try {
                const params = getFilterParams();
                const res = await fetch(`/query-lens/api/requests?${params}&_cb=${Date.now()}`);
                const requests = await res.json();

                renderRequests(requests);
                document.getElementById('request-count').textContent = `${requests.length} requests`;
            } catch (e) {
                console.error('Error loading requests:', e);
            }
        }

        function renderRequests(requests) {
            const container = document.getElementById('request-list');

            if (!requests.length) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No requests found</div>';
                return;
            }

            container.innerHTML = requests.map(req => {
                const isSelected = req.request_id === state.currentRequestId;
                const methodColors = {
                    GET: 'text-emerald-400 bg-emerald-500/10',
                    POST: 'text-blue-400 bg-blue-500/10',
                    PUT: 'text-amber-400 bg-amber-500/10',
                    DELETE: 'text-rose-400 bg-rose-500/10'
                };
                const methodClass = methodColors[req.method] || 'text-slate-400 bg-slate-500/10';

                return `
                    <div onclick="selectRequest('${req.request_id}')"
                         class="px-4 py-3 cursor-pointer transition-colors border-l-2 ${isSelected ? 'bg-indigo-500/10 border-l-indigo-500' : 'border-l-transparent hover:bg-slate-800/50'}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold ${methodClass}">${req.method}</span>
                            <span class="text-[10px] text-slate-500">${formatTime(req.timestamp)}</span>
                        </div>
                        <div class="font-mono text-xs text-slate-300 truncate mb-1">${req.path || '/'}</div>
                        <div class="flex items-center gap-3 text-[10px]">
                            <span class="text-slate-500">${req.query_count} queries</span>
                            <span class="text-slate-500">${(req.avg_time * 1000).toFixed(1)}ms avg</span>
                            ${req.slow_count > 0 ? `<span class="text-rose-400 font-medium">${req.slow_count} slow</span>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function selectRequest(id) {
            state.currentRequestId = id;
            refreshRequests();
            loadQueriesForRequest(id);
            loadWaterfall(id);
        }

        // ==================== Queries ====================
        async function loadQueriesForRequest(requestId) {
            try {
                const params = getFilterParams();
                const res = await fetch(`/query-lens/api/queries?request_id=${requestId}&${params}&_cb=${Date.now()}`);
                const data = await res.json();

                state.currentQueries = data.queries || [];
                renderQueryList(state.currentQueries);
                document.getElementById('queries-info').textContent = `${state.currentQueries.length} queries for request`;
            } catch (e) {
                console.error('Error loading queries:', e);
            }
        }

        function renderQueryList(queries) {
            const container = document.getElementById('query-list');

            if (!queries.length) {
                container.innerHTML = '<div class="p-8 text-center text-slate-500">No queries found</div>';
                return;
            }

            container.innerHTML = queries.map((q, i) => {
                const type = q.analysis?.type || 'OTHER';
                const typeBadge = `badge-${type.toLowerCase()}`;
                const perfRating = q.analysis?.performance?.rating || 'fast';
                const issues = q.analysis?.issues || [];
                const isNPlusOne = issues.some(issue => issue.type === 'n+1');
                const isSlow = q.analysis?.performance?.is_slow || false;
                const isVendor = q.origin?.is_vendor || false;
                const hasSecurityIssue = issues.some(issue => issue.type === 'security');
                const hasPerformanceIssue = issues.some(issue => issue.type === 'performance');

                return `
                    <div class="query-card p-3 hover:bg-slate-800/50 cursor-pointer ${isSlow ? 'border-l-2 border-l-rose-500' : ''}" onclick="showQueryDetails('${q.id}')">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="badge ${typeBadge}">${type}</span>
                                ${isVendor
                                    ? '<span class="badge-source badge-vendor"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Vendor</span>'
                                    : '<span class="badge-source badge-app"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>App</span>'
                                }
                                ${isNPlusOne ? '<span class="badge-issue badge-n-plus-one"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>N+1</span>' : ''}
                                ${isSlow ? '<span class="badge-issue badge-slow-query"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Slow</span>' : ''}
                                ${hasSecurityIssue ? '<span class="badge-issue badge-security"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>Security</span>' : ''}
                                ${hasPerformanceIssue && !isSlow ? '<span class="badge-issue badge-performance"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>Perf</span>' : ''}
                                ${issues.length > 0 && !isNPlusOne && !hasSecurityIssue && !hasPerformanceIssue ? `<span class="badge-issue" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>${issues.length}</span>` : ''}
                            </div>
                            <span class="badge perf-${perfRating} whitespace-nowrap">${(q.time * 1000).toFixed(2)}ms</span>
                        </div>
                        <div class="font-mono text-xs text-slate-400 truncate">${escapeHtml(q.sql)}</div>
                        ${q.origin?.file ? `
                            <div class="flex items-center gap-1 mt-2 text-[10px] ${isVendor ? 'text-slate-600' : 'text-indigo-400/70'}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                <span class="truncate">${q.origin.file.split('/').slice(-2).join('/')}:${q.origin.line}</span>
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }

        // ==================== Waterfall ====================
        async function loadWaterfall(requestId) {
            try {
                const res = await fetch(`/query-lens/api/v2/request/${requestId}/waterfall`);
                const data = await res.json();

                renderWaterfall(data);
                document.getElementById('waterfall-info').textContent =
                    `${data.total_queries} queries, ${(data.total_time * 1000).toFixed(1)}ms total`;
            } catch (e) {
                console.error('Error loading waterfall:', e);
            }
        }

        function renderWaterfall(data) {
            const timeline = data.timeline_data || [];
            const queries = data.queries || [];

            // Show/hide stats and legend
            document.getElementById('waterfall-stats').classList.toggle('hidden', !timeline.length);
            document.getElementById('waterfall-legend').classList.toggle('hidden', !timeline.length);

            if (!timeline.length) {
                document.getElementById('waterfall-chart').innerHTML =
                    '<div class="flex items-center justify-center h-64 text-slate-500">No data</div>';
                return;
            }

            // Calculate stats
            const totalTime = data.total_time * 1000;
            const avgTime = totalTime / timeline.length;
            const slowCount = timeline.filter(t => t.is_slow).length;

            // Update stats
            document.getElementById('wf-total-queries').textContent = timeline.length;
            document.getElementById('wf-total-time').textContent = totalTime.toFixed(1) + 'ms';
            document.getElementById('wf-avg-time').textContent = avgTime.toFixed(2) + 'ms';
            document.getElementById('wf-slow-count').textContent = slowCount;

            // Find max end time for scaling
            const maxTime = Math.max(...timeline.map(t => t.end_ms), 1);

            // Build HTML timeline
            const html = `
                <div class="waterfall-timeline">
                    <!-- Header -->
                    <div class="grid grid-cols-12 gap-2 px-4 py-3 bg-slate-800/80 border-b border-slate-700 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        <div class="col-span-1">#</div>
                        <div class="col-span-1">Type</div>
                        <div class="col-span-5">Timeline (0 → ${maxTime.toFixed(0)}ms)</div>
                        <div class="col-span-2 text-right">Duration</div>
                        <div class="col-span-1 text-right">Offset</div>
                        <div class="col-span-2">Query</div>
                    </div>
                    <!-- Rows -->
                    <div class="divide-y divide-slate-700/50">
                        ${timeline.map((t, i) => {
                            const barLeft = (t.start_ms / maxTime) * 100;
                            const barWidth = Math.max(((t.duration_ms) / maxTime) * 100, 0.5);
                            const color = getTypeColor(t.type);
                            const query = queries[i] || {};

                            return `
                                <div class="grid grid-cols-12 gap-2 px-4 py-3 hover:bg-slate-800/70 transition-colors cursor-pointer ${t.is_slow ? 'bg-rose-500/5 border-l-2 border-l-rose-500' : ''}"
                                     onclick="showQueryDetails('${query.id || ''}')">
                                    <!-- Index -->
                                    <div class="col-span-1 flex items-center gap-1">
                                        <span class="text-sm font-mono font-semibold text-slate-400">${t.index}</span>
                                        ${t.is_slow ? '<span class="text-rose-400 text-xs" title="Slow Query">●</span>' : ''}
                                    </div>
                                    <!-- Type Badge -->
                                    <div class="col-span-1 flex items-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold" style="background: ${color}25; color: ${color};">
                                            ${t.type}
                                        </span>
                                    </div>
                                    <!-- Timeline Bar -->
                                    <div class="col-span-5 flex items-center">
                                        <div class="w-full h-5 bg-slate-900 rounded-full relative overflow-hidden border border-slate-700">
                                            <div class="absolute top-0.5 bottom-0.5 rounded-full transition-all"
                                                 style="left: ${barLeft}%; width: ${barWidth}%; background: linear-gradient(90deg, ${color}, ${color}dd); min-width: 4px;">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Duration -->
                                    <div class="col-span-2 flex items-center justify-end">
                                        <span class="text-sm font-mono ${t.is_slow ? 'text-rose-400 font-bold' : 'text-white'}">
                                            ${t.duration_ms.toFixed(2)}ms
                                        </span>
                                    </div>
                                    <!-- Start Offset -->
                                    <div class="col-span-1 flex items-center justify-end">
                                        <span class="text-xs font-mono text-slate-500">
                                            @${t.start_ms.toFixed(0)}
                                        </span>
                                    </div>
                                    <!-- SQL Preview -->
                                    <div class="col-span-2 flex items-center">
                                        <span class="text-xs text-slate-500 truncate font-mono" title="${escapeHtml(t.sql_preview)}">
                                            ${escapeHtml(t.sql_preview.substring(0, 30))}${t.sql_preview.length > 30 ? '...' : ''}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;

            document.getElementById('waterfall-chart').innerHTML = html;
        }

        // ==================== Query Details ====================
        async function showQueryDetails(id) {
            try {
                const res = await fetch(`/query-lens/api/query/${id}?_cb=${Date.now()}`);
                const query = await res.json();

                if (query.error) {
                    alert('Query not found');
                    return;
                }

                renderQueryDetails(query);
                document.getElementById('detail-panel').classList.remove('hidden');
            } catch (e) {
                console.error('Error loading query details:', e);
            }
        }

        function renderQueryDetails(query) {
            const analysis = query.analysis || {};
            const origin = query.origin || {};
            const recommendations = analysis.recommendations || [];
            const issues = analysis.issues || [];
            const isVendor = origin.is_vendor || false;
            const isSlow = analysis.performance?.is_slow || false;
            const isNPlusOne = query.is_n_plus_one || issues.some(i => i.type === 'n+1');

            // Get issue type colors
            const getIssueColor = (type) => {
                switch(type?.toLowerCase()) {
                    case 'n+1': return 'purple';
                    case 'security': return 'orange';
                    case 'performance': return 'amber';
                    default: return 'rose';
                }
            };

            document.getElementById('detail-content').innerHTML = `
                <div class="space-y-4">
                    <!-- Header with badges -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge badge-${(analysis.type || 'other').toLowerCase()}">${analysis.type || 'QUERY'}</span>
                        ${isVendor
                            ? '<span class="badge-source badge-vendor"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Vendor</span>'
                            : '<span class="badge-source badge-app"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>App</span>'
                        }
                        ${isSlow ? '<span class="badge-issue badge-slow-query"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Slow</span>' : ''}
                        ${isNPlusOne ? '<span class="badge-issue badge-n-plus-one"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>N+1</span>' : ''}
                        <span class="badge perf-${analysis.performance?.rating || 'fast'} ml-auto">${(query.time * 1000).toFixed(3)}ms</span>
                    </div>

                    <!-- SQL -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-medium text-slate-500 uppercase">SQL Statement</label>
                            ${analysis.type === 'SELECT' ? `
                                <button onclick="runExplain('${query.id}')" class="px-2 py-1 bg-indigo-500/20 text-indigo-400 text-xs rounded hover:bg-indigo-500/30 transition-colors">
                                    Run EXPLAIN
                                </button>
                            ` : ''}
                        </div>
                        <pre class="p-3 bg-slate-900 rounded-lg text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre-wrap">${escapeHtml(query.sql)}</pre>
                        <div id="explain-result-${query.id}" class="hidden mt-2"></div>
                    </div>

                    <!-- Bindings -->
                    ${query.bindings && query.bindings.length ? `
                        <div>
                            <label class="text-xs font-medium text-slate-500 uppercase mb-2 block">Bindings (${query.bindings.length})</label>
                            <div class="p-3 bg-slate-900 rounded-lg text-xs font-mono text-slate-300 overflow-x-auto">
                                [${query.bindings.map(b => typeof b === 'string' ? `"${escapeHtml(b)}"` : b).join(', ')}]
                            </div>
                        </div>
                    ` : ''}

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 bg-slate-900 rounded-lg">
                            <label class="text-[10px] text-slate-500 uppercase">Connection</label>
                            <div class="text-sm text-white mt-1">${query.connection || 'default'}</div>
                        </div>
                        <div class="p-3 bg-slate-900 rounded-lg">
                            <label class="text-[10px] text-slate-500 uppercase">Complexity</label>
                            <div class="text-sm text-white mt-1">${analysis.complexity?.level || 'N/A'} (${analysis.complexity?.score || 0})</div>
                        </div>
                        <div class="p-3 bg-slate-900 rounded-lg">
                            <label class="text-[10px] text-slate-500 uppercase">Performance</label>
                            <div class="text-sm mt-1 ${isSlow ? 'text-rose-400' : 'text-emerald-400'}">${analysis.performance?.rating || 'fast'}</div>
                        </div>
                    </div>

                    <!-- Origin -->
                    ${origin.file ? `
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-medium text-slate-500 uppercase">Origin</label>
                                ${isVendor
                                    ? '<span class="badge-source badge-vendor" style="font-size: 9px;"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Vendor Package</span>'
                                    : '<span class="badge-source badge-app" style="font-size: 9px;"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>Application Code</span>'
                                }
                            </div>
                            <div class="p-3 bg-slate-900 rounded-lg font-mono text-xs ${isVendor ? 'text-slate-500' : 'text-indigo-400'} break-all">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                                    <span>${origin.file}:${origin.line}</span>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    <!-- Issues -->
                    ${issues.length ? `
                        <div>
                            <label class="text-xs font-medium text-slate-500 uppercase mb-2 block">Issues (${issues.length})</label>
                            <div class="space-y-2">
                                ${issues.map(i => {
                                    const issueStyles = {
                                        'n+1': { bg: 'rgba(168, 85, 247, 0.1)', border: 'rgba(168, 85, 247, 0.2)', text: '#c084fc', label: '#a855f7' },
                                        'security': { bg: 'rgba(249, 115, 22, 0.1)', border: 'rgba(249, 115, 22, 0.2)', text: '#fdba74', label: '#f97316' },
                                        'performance': { bg: 'rgba(245, 158, 11, 0.1)', border: 'rgba(245, 158, 11, 0.2)', text: '#fcd34d', label: '#f59e0b' },
                                        'default': { bg: 'rgba(244, 63, 94, 0.1)', border: 'rgba(244, 63, 94, 0.2)', text: '#fda4af', label: '#f43f5e' }
                                    };
                                    const style = issueStyles[i.type?.toLowerCase()] || issueStyles.default;
                                    return `
                                        <div class="p-3 rounded-lg" style="background: ${style.bg}; border: 1px solid ${style.border}">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-semibold uppercase" style="color: ${style.label}">${escapeHtml(i.type)}</span>
                                            </div>
                                            <div class="text-xs" style="color: ${style.text}">${escapeHtml(i.message)}</div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <!-- Recommendations -->
                    ${recommendations.length ? `
                        <div>
                            <label class="text-xs font-medium text-slate-500 uppercase mb-2 block">Recommendations</label>
                            <div class="space-y-2">
                                ${recommendations.map(r => `
                                    <div class="p-2 bg-indigo-500/10 border border-indigo-500/20 rounded-lg text-xs text-indigo-300">
                                        ${r}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function closeDetails() {
            document.getElementById('detail-panel').classList.add('hidden');
        }

        // ==================== EXPLAIN ====================
        async function runExplain(id) {
            const container = document.getElementById(`explain-result-${id}`);
            container.classList.remove('hidden');
            container.innerHTML = '<div class="p-3 bg-slate-900 rounded-lg text-slate-400 text-xs">Running EXPLAIN...</div>';

            try {
                const queryRes = await fetch(`/query-lens/api/query/${id}`);
                const query = await queryRes.json();

                const res = await fetch('/query-lens/api/explain', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ sql: query.sql, bindings: query.bindings, connection: query.connection })
                });
                const data = await res.json();

                if (data.error) {
                    container.innerHTML = `<div class="p-3 bg-rose-500/10 rounded-lg text-rose-400 text-xs">${data.error}</div>`;
                    return;
                }

                // Extract the humanized explanation from analyze array
                let humanizedExplain = '';
                if (data.analyze && data.analyze.length > 0) {
                    const firstRow = data.analyze[0];
                    if (firstRow && typeof firstRow === 'object') {
                        // Get the first value (the humanized tree explanation)
                        humanizedExplain = Object.values(firstRow)[0] || '';
                    }
                }

                // Parse markdown in insights
                const parsedInsights = (data.insights || []).map(i => {
                    try {
                        return marked.parse(i);
                    } catch (e) {
                        return escapeHtml(i);
                    }
                });

                container.innerHTML = `
                    <div class="p-3 bg-slate-900 rounded-lg space-y-4">
                        <!-- Summary Section -->
                        <div>
                            <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block mb-1">Summary</label>
                            <div class="text-sm text-slate-200 markdown-content">${marked.parse(data.summary || 'No summary available.')}</div>
                        </div>

                        <!-- Insights Section -->
                        ${parsedInsights.length ? `
                            <div>
                                <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block mb-2">Insights</label>
                                <div class="space-y-2">
                                    ${parsedInsights.map(i => `<div class="text-xs text-slate-300 markdown-content bg-slate-800/50 p-2 rounded border border-slate-700/50">${i}</div>`).join('')}
                                </div>
                            </div>
                        ` : ''}

                        <!-- Humanized Execution Plan -->
                        ${humanizedExplain ? `
                            <div>
                                <label class="text-[10px] font-medium text-slate-500 uppercase tracking-wider block mb-2">Execution Plan (Humanized)</label>
                                <pre class="p-3 bg-black/50 rounded-lg text-[11px] font-mono text-emerald-400 overflow-x-auto whitespace-pre-wrap leading-relaxed border border-slate-700/50">${escapeHtml(humanizedExplain)}</pre>
                            </div>
                        ` : ''}

                        <!-- Raw Output (Collapsible) -->
                        ${data.raw_analyze ? `
                            <details class="group">
                                <summary class="text-[10px] font-medium text-slate-500 uppercase tracking-wider cursor-pointer hover:text-slate-400 flex items-center gap-1">
                                    <svg class="w-3 h-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    Raw EXPLAIN ANALYZE Output
                                </summary>
                                <pre class="mt-2 p-2 bg-black/30 rounded text-[10px] font-mono text-slate-500 overflow-x-auto whitespace-pre-wrap">${escapeHtml(data.raw_analyze)}</pre>
                            </details>
                        ` : ''}
                    </div>
                `;
            } catch (e) {
                container.innerHTML = `<div class="p-3 bg-rose-500/10 rounded-lg text-rose-400 text-xs">Error: ${e.message}</div>`;
            }
        }

        // ==================== Alerts ====================
        async function loadAlerts() {
            try {
                const res = await fetch('/query-lens/api/v2/alerts');
                const data = await res.json();

                renderAlerts(data.alerts || []);
                renderAlertLogs(data.stats);
            } catch (e) {
                console.error('Error loading alerts:', e);
            }
        }

        function renderAlerts(alerts) {
            const container = document.getElementById('alerts-list');

            if (!alerts.length) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No alerts configured</div>';
                return;
            }

            container.innerHTML = alerts.map(a => `
                <div class="p-3 flex items-center justify-between hover:bg-slate-800/50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-white">${escapeHtml(a.name)}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] ${a.enabled ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-400'}">
                                ${a.enabled ? 'Active' : 'Disabled'}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">${a.type} &bull; Triggered ${a.trigger_count} times</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="toggleAlert(${a.id})" class="p-1.5 rounded hover:bg-slate-700 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${a.enabled ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M5 13l4 4L19 7'}"></path>
                            </svg>
                        </button>
                        <button onclick="deleteAlert(${a.id})" class="p-1.5 rounded hover:bg-slate-700 text-slate-400 hover:text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        async function loadAlertLogs() {
            try {
                const res = await fetch('/query-lens/api/v2/alerts/logs?hours=24&limit=20');
                const data = await res.json();

                const container = document.getElementById('alert-logs');

                if (!data.logs?.length) {
                    container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No recent alerts</div>';
                    return;
                }

                container.innerHTML = data.logs.map(log => `
                    <div class="p-3 hover:bg-slate-800/50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-white">${escapeHtml(log.alert_name)}</span>
                            <span class="text-[10px] text-slate-500">${formatTime(new Date(log.created_at).getTime() / 1000)}</span>
                        </div>
                        <div class="text-xs text-slate-400">${escapeHtml(log.message)}</div>
                    </div>
                `).join('');
            } catch (e) {
                console.error('Error loading alert logs:', e);
            }
        }

        function showCreateAlertModal() {
            document.getElementById('alert-modal').classList.remove('hidden');
            clearAlertFormErrors();
            updateThresholdLabel();
        }

        function hideAlertModal() {
            document.getElementById('alert-modal').classList.add('hidden');
            document.getElementById('alert-form').reset();
            clearAlertFormErrors();
            setAlertSubmitLoading(false);
        }

        function clearAlertFormErrors() {
            document.getElementById('alert-form-errors').classList.add('hidden');
            ['name', 'type', 'threshold', 'channels', 'cooldown'].forEach(field => {
                const errorEl = document.getElementById(`alert-${field}-error`);
                const inputEl = document.getElementById(`alert-${field}`);
                if (errorEl) {
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }
                if (inputEl) {
                    inputEl.classList.remove('border-rose-500');
                }
            });
        }

        function showFieldError(field, message) {
            const errorEl = document.getElementById(`alert-${field}-error`);
            const inputEl = document.getElementById(`alert-${field}`);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
            if (inputEl) {
                inputEl.classList.add('border-rose-500');
            }
        }

        function showFormError(message) {
            const errorBanner = document.getElementById('alert-form-errors');
            const errorText = document.getElementById('alert-form-error-text');
            errorText.textContent = message;
            errorBanner.classList.remove('hidden');
        }

        function setAlertSubmitLoading(loading) {
            const btn = document.getElementById('alert-submit-btn');
            const text = document.getElementById('alert-submit-text');
            const spinner = document.getElementById('alert-submit-spinner');

            btn.disabled = loading;
            text.textContent = loading ? 'Creating...' : 'Create Alert';
            spinner.classList.toggle('hidden', !loading);
        }

        function updateThresholdLabel() {
            const type = document.getElementById('alert-type').value;
            const label = document.getElementById('threshold-label');
            const unit = document.getElementById('threshold-unit');
            const hint = document.getElementById('threshold-hint');
            const input = document.getElementById('alert-threshold');
            const container = document.getElementById('threshold-container');

            switch (type) {
                case 'slow_query':
                    label.textContent = 'Time Threshold';
                    unit.textContent = 'sec';
                    hint.textContent = 'Query execution time threshold (0.01 - 3600 seconds)';
                    input.min = '0.01';
                    input.max = '3600';
                    input.step = '0.01';
                    input.value = input.value || '1.0';
                    container.classList.remove('hidden');
                    break;
                case 'n_plus_one':
                    label.textContent = 'Minimum Similar Queries';
                    unit.textContent = 'count';
                    hint.textContent = 'Minimum number of similar queries to trigger (1 - 100)';
                    input.min = '1';
                    input.max = '100';
                    input.step = '1';
                    input.value = '5';
                    container.classList.remove('hidden');
                    break;
                case 'threshold':
                    label.textContent = 'Metric Threshold';
                    unit.textContent = 'sec';
                    hint.textContent = 'Custom metric threshold value';
                    input.min = '0.01';
                    input.max = '3600';
                    input.step = '0.01';
                    input.value = input.value || '1.0';
                    container.classList.remove('hidden');
                    break;
                case 'error_rate':
                    label.textContent = 'Minimum Issues';
                    unit.textContent = 'count';
                    hint.textContent = 'Minimum number of issues to trigger (1 - 10)';
                    input.min = '1';
                    input.max = '10';
                    input.step = '1';
                    input.value = '1';
                    container.classList.remove('hidden');
                    break;
                default:
                    container.classList.add('hidden');
            }
        }

        function validateAlertForm() {
            clearAlertFormErrors();
            let isValid = true;
            const errors = [];

            // Validate name
            const name = document.getElementById('alert-name').value.trim();
            if (!name) {
                showFieldError('name', 'Alert name is required');
                errors.push('Name is required');
                isValid = false;
            } else if (name.length < 3) {
                showFieldError('name', 'Alert name must be at least 3 characters');
                errors.push('Name too short');
                isValid = false;
            } else if (name.length > 255) {
                showFieldError('name', 'Alert name must be less than 255 characters');
                errors.push('Name too long');
                isValid = false;
            }

            // Validate type
            const type = document.getElementById('alert-type').value;
            if (!type) {
                showFieldError('type', 'Please select an alert type');
                errors.push('Type is required');
                isValid = false;
            } else if (!['slow_query', 'n_plus_one', 'threshold', 'error_rate'].includes(type)) {
                showFieldError('type', 'Invalid alert type selected');
                errors.push('Invalid type');
                isValid = false;
            }

            // Validate threshold
            const threshold = parseFloat(document.getElementById('alert-threshold').value);
            if (isNaN(threshold)) {
                showFieldError('threshold', 'Threshold must be a valid number');
                errors.push('Invalid threshold');
                isValid = false;
            } else if (threshold <= 0) {
                showFieldError('threshold', 'Threshold must be greater than 0');
                errors.push('Threshold must be positive');
                isValid = false;
            } else if (type === 'slow_query' && threshold > 3600) {
                showFieldError('threshold', 'Time threshold cannot exceed 3600 seconds');
                errors.push('Threshold too high');
                isValid = false;
            } else if (type === 'n_plus_one' && (threshold < 1 || threshold > 100 || !Number.isInteger(threshold))) {
                showFieldError('threshold', 'Must be a whole number between 1 and 100');
                errors.push('Invalid N+1 count');
                isValid = false;
            }

            // Validate channels
            const channels = document.querySelectorAll('input[name="channels[]"]:checked');
            if (channels.length === 0) {
                showFieldError('channels', 'Select at least one notification channel');
                errors.push('No channels selected');
                isValid = false;
            }

            // Validate cooldown
            const cooldown = parseInt(document.getElementById('alert-cooldown').value);
            if (isNaN(cooldown)) {
                showFieldError('cooldown', 'Cooldown must be a valid number');
                errors.push('Invalid cooldown');
                isValid = false;
            } else if (cooldown < 1 || cooldown > 1440) {
                showFieldError('cooldown', 'Cooldown must be between 1 and 1440 minutes');
                errors.push('Cooldown out of range');
                isValid = false;
            }

            if (!isValid) {
                showFormError(`Please fix ${errors.length} error${errors.length > 1 ? 's' : ''} before submitting`);
            }

            return isValid;
        }

        async function createAlert(e) {
            e.preventDefault();

            if (!validateAlertForm()) {
                return;
            }

            const form = e.target;
            const formData = new FormData(form);
            const type = formData.get('type');

            const channels = [];
            form.querySelectorAll('input[name="channels[]"]:checked').forEach(cb => channels.push(cb.value));

            // Build conditions based on type
            const threshold = parseFloat(formData.get('threshold'));
            const conditions = {};

            switch (type) {
                case 'slow_query':
                    conditions.threshold = threshold;
                    break;
                case 'n_plus_one':
                    conditions.min_count = Math.floor(threshold);
                    break;
                case 'threshold':
                    conditions.threshold = threshold;
                    conditions.metric = 'time';
                    conditions.operator = '>=';
                    break;
                case 'error_rate':
                    conditions.min_issues = Math.floor(threshold);
                    break;
            }

            setAlertSubmitLoading(true);

            try {
                const res = await fetch('/query-lens/api/v2/alerts', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        name: formData.get('name').trim(),
                        type: type,
                        conditions: conditions,
                        channels: channels,
                        cooldown_minutes: parseInt(formData.get('cooldown_minutes'))
                    })
                });

                const data = await res.json();

                if (res.ok) {
                    hideAlertModal();
                    loadAlerts();
                    // Show success toast
                    showToast('Alert created successfully', 'success');
                } else {
                    // Handle server validation errors
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            showFieldError(field, messages[0]);
                        });
                        showFormError('Server validation failed');
                    } else if (data.message) {
                        showFormError(data.message);
                    } else {
                        showFormError('Failed to create alert. Please try again.');
                    }
                }
            } catch (err) {
                console.error('Error creating alert:', err);
                showFormError('Network error. Please check your connection and try again.');
            } finally {
                setAlertSubmitLoading(false);
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-indigo-500';
            toast.className = `fixed bottom-4 right-4 px-4 py-2 ${bgColor} text-white text-sm rounded-lg shadow-lg z-50 animate-fade-in`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        async function toggleAlert(id) {
            try {
                await fetch(`/query-lens/api/v2/alerts/${id}/toggle`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                loadAlerts();
            } catch (e) {
                console.error('Error toggling alert:', e);
            }
        }

        async function deleteAlert(id) {
            if (!confirm('Delete this alert?')) return;
            try {
                await fetch(`/query-lens/api/v2/alerts/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                loadAlerts();
            } catch (e) {
                console.error('Error deleting alert:', e);
            }
        }

        // ==================== Tabs ====================
        function switchTab(tab) {
            state.currentTab = tab;

            document.querySelectorAll('.tab').forEach(t => {
                t.classList.toggle('active', t.dataset.tab === tab);
            });

            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.toggle('hidden', c.id !== `tab-${tab}`);
            });

            // Load data for tab
            if (tab === 'top-queries') {
                loadTopQueries('slowest');
                loadTopQueries('most_frequent');
            } else if (tab === 'alerts') {
                loadAlerts();
                loadAlertLogs();
            } else if (tab === 'trends') {
                loadTrendsChart();
            }
        }

        // ==================== Filters ====================
        function getFilterParams() {
            const params = new URLSearchParams();
            const type = document.getElementById('type-filter').value;
            const issue = document.getElementById('issue-filter').value;
            const sort = document.getElementById('sort-by').value;
            const order = document.getElementById('sort-order').value;

            if (type) params.append('type', type);
            if (issue) params.append('issue_type', issue);
            params.append('sort', sort);
            params.append('order', order);

            return params.toString();
        }

        function applyFilters() {
            refreshRequests();
            if (state.currentRequestId) {
                loadQueriesForRequest(state.currentRequestId);
            }
        }

        function resetFilters() {
            document.getElementById('type-filter').value = '';
            document.getElementById('issue-filter').value = '';
            document.getElementById('sort-by').value = 'timestamp';
            document.getElementById('sort-order').value = 'desc';
            applyFilters();
        }

        // ==================== Actions ====================
        async function resetQueries() {
            if (!confirm('Clear all query history?')) return;

            try {
                await fetch('/query-lens/api/reset', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                state.currentRequestId = null;
                state.currentQueries = [];
                refreshRequests();
                loadOverviewStats();
                renderQueryList([]);
            } catch (e) {
                console.error('Error resetting:', e);
            }
        }

        async function exportData(format) {
            try {
                const res = await fetch('/query-lens/api/export', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ format })
                });
                const data = await res.json();

                const blob = new Blob([typeof data.data === 'string' ? data.data : JSON.stringify(data.data, null, 2)], {
                    type: format === 'csv' ? 'text/csv' : 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.filename;
                a.click();
            } catch (e) {
                console.error('Error exporting:', e);
            }
        }

        // ==================== Utilities ====================
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatNumber(n) {
            return new Intl.NumberFormat().format(n);
        }

        function formatMs(seconds) {
            return (seconds * 1000).toFixed(1) + 'ms';
        }

        function formatTime(ts) {
            return new Date(ts * 1000).toLocaleTimeString();
        }

        function getTypeColor(type) {
            const colors = {
                SELECT: '#3b82f6',
                INSERT: '#10b981',
                UPDATE: '#f59e0b',
                DELETE: '#ef4444',
                CACHE: '#8b5cf6',
                OTHER: '#8b5cf6'
            };
            return colors[type?.toUpperCase()] || '#64748b';
        }

        function getPeriodDates() {
            const period = document.getElementById('period-select').value;
            const end = new Date().toISOString();
            let start;

            switch (period) {
                case '1h': start = new Date(Date.now() - 3600000).toISOString(); break;
                case '7d': start = new Date(Date.now() - 7 * 86400000).toISOString(); break;
                default: start = new Date(Date.now() - 86400000).toISOString();
            }

            return { start, end };
        }

        function onPeriodChange() {
            // Refresh all data that depends on the selected period
            loadTrendsChart();
            loadTopQueries('slowest');
            loadTopQueries('most_frequent');
            loadOverviewStats();
        }

        function showTopQueryDetail(sql) {
            alert(sql); // Simple preview, could be modal
        }
    </script>
</body>
</html>
