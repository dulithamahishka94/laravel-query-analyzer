<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Query Analyzer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .query-card { transition: all 0.2s ease; border-left: 4px solid transparent; }
        .query-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        
        .type-select { border-left-color: #3b82f6; }
        .type-insert { border-left-color: #10b981; }
        .type-update { border-left-color: #f59e0b; }
        .type-delete { border-left-color: #ef4444; }


        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { transition: all 0.2s ease-out; }
        .modal-leave-active { transition: all 0.15s ease-in; opacity: 0; transform: scale(0.95); }
    </style>
</head>
<body class="text-slate-800 h-screen flex flex-col overflow-hidden">
    
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 z-10 shadow-sm flex-none">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-12 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 text-white p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-600">Query Analyzer v3 (Final Rewrite)</h1>
                </div>

                <div class="hidden md:flex items-center gap-6 border-l border-slate-100 pl-8 h-8">
                    <div class="flex items-baseline gap-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total</span>
                        <span class="text-sm font-bold text-slate-700 tabular-nums" id="total-queries">-</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Slow</span>
                        <span class="text-sm font-bold text-red-500 tabular-nums" id="slow-queries">-</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg</span>
                        <span class="text-sm font-bold text-indigo-600 tabular-nums" id="avg-time">-</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                 <div class="flex items-center gap-2 px-2 py-1 bg-slate-50 rounded-full border border-slate-100 text-[10px] font-bold text-slate-500 uppercase">
                    <span class="relative flex h-1.5 w-1.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500"></span>
                    </span>
                    {{ $isEnabled ? 'Live' : 'Paused' }}
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex overflow-hidden">
        <!-- Sidebar Controls -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col flex-none z-0">
            <div class="flex-1 overflow-y-auto p-4 space-y-8">
                <!-- Filter Section -->
                <section>
                    <div class="flex items-center gap-2 mb-4 opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        <h3 class="text-[10px] font-bold uppercase tracking-[0.2em]">Filters</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Query Type</label>
                            <select id="type-filter" onchange="filterQueries()" class="w-full text-xs border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 p-2">
                                <option value="all">All Types</option>
                                <option value="select">SELECT</option>
                                <option value="insert">INSERT</option>
                                <option value="update">UPDATE</option>
                                <option value="delete">DELETE</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Performance</label>
                            <select id="rating-filter" onchange="filterQueries()" class="w-full text-xs border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 p-2">
                                <option value="all">Any Rating</option>
                                <option value="fast">Fast</option>
                                <option value="moderate">Moderate</option>
                                <option value="slow">Slow</option>
                                <option value="very_slow">Very Slow</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Sort Section -->
                <section>
                    <div class="flex items-center gap-2 mb-4 opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                        <h3 class="text-[10px] font-bold uppercase tracking-[0.2em]">Sorting</h3>
                    </div>

                    <div class="space-y-2">
                        <select id="sort-by" onchange="filterQueries()" class="w-full text-xs border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 p-2">
                            <option value="timestamp">By Timestamp</option>
                            <option value="time">By Execution Time</option>
                            <option value="complexity">By Complexity</option>
                        </select>
                        <select id="sort-order" onchange="filterQueries()" class="w-full text-xs border-slate-200 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 p-2">
                            <option value="desc">Descending</option>
                            <option value="asc">Ascending</option>
                        </select>
                    </div>
                </section>

                <!-- Settings Section -->
                <section>
                    <div class="flex items-center gap-2 mb-4 opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h3 class="text-[10px] font-bold uppercase tracking-[0.2em]">Options</h3>
                    </div>
                    <label class="flex items-center justify-between p-2.5 bg-slate-50/50 rounded-lg cursor-pointer group hover:bg-slate-100/50 transition-colors border border-slate-100">
                        <span class="text-xs font-semibold text-slate-600">Auto-refresh</span>
                        <input type="checkbox" id="auto-refresh" onchange="toggleAutoRefresh()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    </label>
                </section>
            </div>

            <!-- Operations Footer -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/30 space-y-2">
                <button onclick="refreshQueries()" class="w-full flex justify-center items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg shadow-sm text-xs font-bold hover:bg-indigo-700 active:scale-[0.98] transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
                <button onclick="resetQueries()" class="w-full flex justify-center items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-red-500 rounded-lg text-[10px] font-bold hover:bg-red-50 hover:text-red-600 active:scale-[0.98] transition-all">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Clear History
                </button>
                
                <div class="grid grid-cols-2 gap-2 pt-2">
                     <button onclick="exportQueries('json')" class="text-[9px] font-bold py-1.5 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 rounded-md transition-all uppercase tracking-tight">JSON</button>
                     <button onclick="exportQueries('csv')" class="text-[9px] font-bold py-1.5 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 rounded-md transition-all uppercase tracking-tight">CSV</button>
                </div>
            </div>
        </aside>

        <!-- Query List -->
        <div class="flex-1 flex flex-col bg-slate-50 min-w-0">
            <div class="px-6 py-3 border-b border-slate-200 bg-white flex justify-between items-center flex-none">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-700">Captured Queries</h2>
                    <span id="query-count" class="bg-indigo-50 text-indigo-600 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-100 transition-all">0</span>
                </div>
            </div>
            
            <div id="query-list" class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Queries injected here -->
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                    Loading queries...
                </div>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div id="query-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                    <div id="modal-content" class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 max-h-[85vh] overflow-y-auto">
                         <!-- Content injected here -->
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                        <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener('DOMContentLoaded', refreshQueries);

        async function refreshQueries() {
            try {
                const typeFilter = document.getElementById('type-filter').value;
                const ratingFilter = document.getElementById('rating-filter').value;
                const sortBy = document.getElementById('sort-by').value;
                const sortOrder = document.getElementById('sort-order').value;

                const params = new URLSearchParams();
                if (typeFilter !== 'all') params.append('type', typeFilter);
                if (ratingFilter !== 'all') params.append('rating', ratingFilter);
                params.append('sort', sortBy);
                params.append('order', sortOrder);

                const response = await fetch(`/query-analyzer/api/queries?${params}`);
                const data = await response.json();

                updateStats(data.stats);
                renderQueries(data.queries);
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function updateStats(stats) {
            document.getElementById('total-queries').textContent = stats.total_queries;
            document.getElementById('slow-queries').textContent = stats.slow_queries;
            document.getElementById('avg-time').textContent = stats.average_time.toFixed(3) + 's';
            document.getElementById('query-count').textContent = stats.total_queries;
        }

        function renderQueries(queries) {
            const container = document.getElementById('query-list');
            
            if (queries.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <h3 class="text-lg font-medium text-slate-900">No queries found</h3>
                        <p class="text-slate-500">Execute some database interactions to see them here.</p>
                    </div>`;
                return;
            }

            container.innerHTML = queries.map((query, index) => {
                const typeClass = `type-${query?.analysis?.type?.toLowerCase() || 'other'}`;
                const fileShort = query?.origin?.file && query?.origin?.file !== 'unknown' 
                    ? query.origin.file.split('/').slice(-2).join('/') + ':' + query.origin.line
                    : '';
                const requestId = query?.request_id ? query.request_id.substring(0, 8) + '...' : '';

                const issues = query?.analysis?.issues || [];
                const hasNPlusOne = issues.some(i => i.type === 'n+1');
                const recommendations = query?.analysis?.recommendations || [];

                console.log(query);

                return `
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 query-card ${typeClass} p-4 cursor-pointer" onclick="showQueryDetails('${query.id}')">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                             <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700">${query?.analysis?.type || 'QUERY'}</span>
                             ${fileShort ? `<span class="text-xs font-mono text-slate-400 flex items-center gap-1" title="${query.origin.file}"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg> ${fileShort}</span>` : ''}
                             ${requestId ? `<span class="text-xs font-mono text-slate-400 bg-slate-50 px-1 rounded" title="Request ID: ${query.request_id}">req:${requestId}</span>` : ''}
                        </div>
                        <div class="flex items-center gap-2">
                            ${hasNPlusOne ? `<span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-600 flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path></svg> N+1</span>` : ''}
                            ${issues.length > 0 && !hasNPlusOne ? `<span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600 flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg> ${issues.length}</span>` : ''}
                            <span class="px-2 py-0.5 rounded text-xs font-bold ${getPerfClass(query?.analysis?.performance?.rating)}">${(query?.time || 0).toFixed(4)}s</span>
                        </div>
                    </div>
                    
                    <div class="font-mono text-sm text-slate-700 bg-slate-50 p-2 rounded mb-2 overflow-hidden text-ellipsis whitespace-nowrap">
                        ${escapeHtml(query?.sql || '')}
                    </div>
                    
                    ${recommendations.length > 0 ? `
                         <div class="text-xs text-indigo-600 mt-2 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            ${recommendations[0] || ''} ${recommendations.length > 1 ? `(+${recommendations.length - 1} more)` : ''}
                         </div>
                    ` : ''}
                </div>`;
            }).join('');
        }
        
        function getPerfClass(rating) {
            if (rating === 'fast') return 'bg-green-100 text-green-700';
            if (rating === 'moderate') return 'bg-yellow-100 text-yellow-700';
            if (rating === 'slow') return 'bg-orange-100 text-orange-700';
            return 'bg-red-100 text-red-700';
        }

        async function showQueryDetails(id) {
            try {
                console.group('Query Analyzer Detail Fetch');
                console.log('Fetching ID:', id);
                
                const response = await fetch(`/query-analyzer/api/query/${id}?_cb=${Date.now()}`);
                const query = await response.json();
                console.log('Fetched Query Data:', query);
                console.groupEnd();

                if (!query || query.error) {
                    throw new Error(query?.error || 'Query not found on server.');
                }
                
                const modal = document.getElementById('modal-content');
                
                const perfRating = query?.analysis?.performance?.rating || 'unknown';
                const complexity = query?.analysis?.complexity || {level: 'N/A', score: 0};
                const origin = query?.origin || {file: 'unknown', line: 0};
                const recommendations = query?.analysis?.recommendations || [];
                const issues = query?.analysis?.issues || [];

                modal.innerHTML = `
                    <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
                        <h3 class="text-xl font-bold text-slate-900">Query Details</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-bold ${getPerfClass(perfRating)}">${(query?.time || 0).toFixed(5)}s</span>
                    </div>

                    <div class="mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Query Origin & Metadata</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                            <p class="break-all"><strong>File:</strong> <span class="text-indigo-600 font-mono text-xs">${origin.file}:${origin.line}</span></p>
                            <p><strong>Request ID:</strong> <span class="font-mono text-xs bg-slate-100 px-1 rounded">${query.request_id || 'N/A'}</span></p>
                            <p><strong>Performance:</strong> <span class="${getPerfClass(perfRating)} px-2 py-0.5 rounded text-xs font-bold uppercase">${perfRating}</span></p>
                            <p><strong>Complexity:</strong> ${complexity.level} (Score: ${complexity.score})</p>
                            <p><strong>Connection:</strong> <span class="font-mono text-xs">${query.connection || 'default'}</span></p>
                            <p><strong>Timestamp:</strong> ${new Date((query.timestamp || 0) * 1000).toLocaleString()}</p>
                        </div>
                    </div>

                    <div class="mb-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <div class="flex justify-between items-center mb-2">
                             <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">SQL Statement</h4>
                             ${query?.analysis?.type === 'SELECT' ? `<button onclick="runExplain('${query.id}')" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded font-medium flex items-center gap-1 transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg> Run Explain</button>` : ''}
                        </div>
                        <pre class="bg-indigo-900 text-white p-4 rounded-lg font-mono text-sm overflow-x-auto">${escapeHtml(query?.sql || '')}</pre>
                        
                        <div id="explain-result-${query.id}" class="hidden mt-4 bg-white border border-slate-200 rounded p-4 font-mono text-xs overflow-x-auto"></div>
                    </div>

                    ${query.bindings && query.bindings.length > 0 ? `
                    <div class="mb-6">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Bindings</h4>
                         <div class="bg-slate-100 p-3 rounded font-mono text-xs text-slate-600">
                             [ ${query.bindings.map(b => typeof b === 'string' ? `"${b}"` : b).join(', ')} ]
                         </div>
                    </div>` : ''}

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                             <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Recommendations</h4>
                             ${recommendations.length > 0  ? 
                                `<ul class="space-y-2">
                                    ${recommendations.map(rec => `<li class="flex items-start gap-2 text-sm text-indigo-700 bg-indigo-50 p-2 rounded"><svg class="w-4 h-4 mt-0.5 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> ${rec}</li>`).join('')}
                                </ul>` 
                                : '<div class="text-sm text-slate-400 italic">No recommendations. Good job!</div>'}
                        </div>
                        
                        <div>
                             <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Issues & Warnings</h4>
                             ${issues.length > 0 ? 
                                `<ul class="space-y-2">
                                    ${issues.map(issue => `<li class="flex items-start gap-2 text-sm text-red-700 bg-red-50 p-2 rounded"><svg class="w-4 h-4 mt-0.5 flex-none" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg> <strong>${issue?.type || 'Issue'}:</strong> ${issue?.message || 'Warning detected'}</li>`).join('')}
                                </ul>` 
                                : '<div class="text-sm text-slate-400 italic">No issues detected.</div>'}
                        </div>
                    </div>
                `;

                document.getElementById('query-modal').classList.remove('hidden');
            } catch (error) {
                console.error('Modal Error:', error);
                alert('Could not show query details: ' + error.message);
            }
        }

        function closeModal() {
            document.getElementById('query-modal').classList.add('hidden');
        }

        function filterQueries() { refreshQueries(); }
        function toggleAutoRefresh() {
             const checkbox = document.getElementById('auto-refresh');
             if (checkbox.checked) {
                 autoRefreshInterval = setInterval(refreshQueries, 5000);
             } else {
                 if (autoRefreshInterval) clearInterval(autoRefreshInterval);
             }
        }
        
        async function resetQueries() {
             if(confirm('Reset all queries?')) {
                 await fetch('/query-analyzer/api/reset', { method: 'POST', headers: {'X-CSRF-TOKEN': csrfToken} });
                 refreshQueries();
             }
        }
        
        async function runExplain(id) {
            const container = document.getElementById(`explain-result-${id}`);
            if (!container) return;
            
            container.classList.remove('hidden');
            container.innerHTML = '<div class="flex items-center gap-2 text-slate-500 font-sans p-4"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating deep insights...</div>';

            try {
                console.group('Explain Call Details');
                console.log('Fetching query details for ID:', id);

                const queryRes = await fetch(`/query-analyzer/api/query/${id}?_cb=${Date.now()}`);
                if (!queryRes.ok) throw new Error(`HTTP ${queryRes.status} fetching query`);
                const query = await queryRes.json();
                console.log('Query fetched for verify:', query.sql);

                const response = await fetch(`/query-analyzer/api/explain?_cb=${Date.now()}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ sql: query.sql, bindings: query.bindings, connection: query.connection })
                });

                if (!response.ok) throw new Error(`HTTP ${response.status} running explain`);
                const data = await response.json();
                console.log('Explain logic output:', data);
                console.groupEnd();

                if (!data || data.error) {
                    container.innerHTML = `<div class="text-red-600 font-semibold p-4">Error: ${data?.error || 'Unknown server error'}</div>`;
                    return;
                }

                // Defensive normalization
                const standard = Array.isArray(data.standard) ? data.standard : [];
                const analyze = Array.isArray(data.analyze) ? data.analyze : [];
                const insights = Array.isArray(data.insights) ? data.insights : [];
                const summary = data.summary || 'No summary available.';

                // Detect if Standard is a tree (single column with long content)
                const standardIsTree = standard.length === 1 && Object.keys(standard[0] || {}).length === 1;

                let output = `
                    <div class="mb-4 flex justify-between items-center border-b border-slate-100 pb-2">
                        <div class="flex flex-col">
                             <span class="text-indigo-600 font-bold uppercase text-[10px] tracking-widest">Deep Analysis Results</span>
                             <span class="text-[9px] text-slate-400 font-mono mt-0.5 truncate max-w-sm">Verifying SQL: ${escapeHtml(query.sql.substring(0, 70))}...</span>
                        </div>
                        <button onclick="document.getElementById('explain-result-${id}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
                    </div>
                    <div class="mb-6 bg-slate-900 text-slate-100 p-4 rounded-lg shadow-inner border-l-4 border-indigo-500">
                        <h5 class="text-[10px] uppercase font-bold text-indigo-400 mb-2 tracking-tight">Humanized Summary</h5>
                        <p class="text-sm leading-relaxed font-sans">${summary}</p>
                    </div>

                    <!-- Bullet Insights -->
                    ${insights.length > 0 ? `
                    <div class="mb-6 space-y-2">
                        ${insights.map(i => `<div class="bg-indigo-50 border-l-4 border-indigo-400 p-2 text-xs text-indigo-800 font-medium shadow-sm">${i}</div>`).join('')}
                    </div>` : ''}

                    <!-- Dual View Profiles -->
                    <div class="space-y-6">
                        <!-- 1. Standard Plan -->
                        ${standard.length > 0 ? `
                        <div>
                            <h5 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-widest">Standard Execution Plan</h5>
                            ${standardIsTree ? `
                                <pre class="whitespace-pre-wrap bg-slate-50 p-3 rounded border border-slate-100 font-mono text-[10px] leading-tight text-slate-600">${escapeHtml(String(Object.values(standard[0])[0] || ''))}</pre>
                            ` : `
                                <div class="overflow-x-auto border border-slate-200 rounded">
                                    <table class="w-full text-left border-collapse min-w-full">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200">
                                                ${Object.keys(standard[0] || {}).map(k => `<th class="p-2 font-bold text-slate-500 text-[9px] uppercase tracking-wider border-r border-slate-200 last:border-0">${k}</th>`).join('')}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${standard.map(row => `
                                                <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                                                    ${Object.values(row || {}).map(v => `<td class="p-2 border-r border-slate-100 last:border-0 text-[11px] text-slate-600">${v === null ? '<span class="text-slate-300 italic">null</span>' : escapeHtml(String(v))}</td>`).join('')}
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `}
                        </div>` : ''}

                        <!-- 2. ANALYZE Profile Tree -->
                        ${data.supports_analyze && analyze.length > 0 && analyze[0] ? `
                        <div>
                            <h5 class="text-[10px] uppercase font-bold text-slate-400 mb-2 tracking-widest">Profiling Tree (Analyze)</h5>
                            <pre class="whitespace-pre-wrap bg-slate-800 text-indigo-300 p-4 rounded font-mono text-[11px] leading-tight border border-slate-700 shadow-lg">${escapeHtml(String(Object.values(analyze[0])[0] || ''))}</pre>
                        </div>` : ''}
                    </div>
                `;

                container.innerHTML = output;

            } catch (error) {
                console.error('Explain Error Details:', error);
                container.innerHTML = `<div class="text-red-600 font-semibold p-4">System Error: ${error.message} (See console for details)</div>`;
            }
        }

        async function exportQueries(format) {
            const res = await fetch('/query-analyzer/api/export', { 
                method: 'POST', 
                headers: {'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json'},
                body: JSON.stringify({format})
            });
            const data = await res.json();
            const blob = new Blob([data.data], {type: format === 'csv' ? 'text/csv' : 'application/json'});
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a'); a.href = url; a.download = data.filename; a.click();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>