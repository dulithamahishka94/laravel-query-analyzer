
    // ==================== Top Queries ====================
    async function loadTopQueries(type) {
        const periodEl = type === 'slowest' ? 'slowest-period' : 'frequent-period';
        const period = document.getElementById(periodEl).value;
        const containerId = type === 'slowest' ? 'slowest-queries' : 'frequent-queries';

        try {
            const res = await fetch(`/query-lens/api/v2/top-queries?type=${type}&period=${period}&limit=5`);
            const data = await res.json();
            renderTopQueries(data.queries, containerId);
        } catch (e) {
            console.error('Error loading top queries:', e);
        }
    }

    function renderTopQueries(queries, containerId) {
        const container = document.getElementById(containerId);
        if (!queries.length) {
            container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No data available</div>';
            return;
        }

        container.innerHTML = queries.map((q, i) => `
            <div class="p-3 hover:bg-slate-800/50 transition-colors cursor-pointer group" onclick="showTopQueryDetail('${escapeHtml(q.sql_sample)}')">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span class="text-xs font-mono text-slate-500">#${i + 1}</span>
                        <code class="text-xs font-mono text-indigo-300 truncate group-hover:text-indigo-200 transition-colors">
                            ${escapeHtml(q.sql_sample)}
                        </code>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <div class="text-xs font-bold ${q.avg_time > 1 ? 'text-rose-400' : 'text-slate-300'}">
                            ${formatMs(q.avg_time)}
                        </div>
                        <div class="text-[10px] text-slate-500">${formatNumber(q.count)} calls</div>
                    </div>
                </div>
                ${q.issue_count > 0 ? `
                    <div class="flex items-center gap-1 mt-1">
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            ${q.issue_count} issue${q.issue_count > 1 ? 's' : ''} detected
                        </span>
                    </div>
                ` : ''}
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

        // Switch to queries tab when a request is selected
        switchTab('queries');
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
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            <span class="truncate mr-1" title="${q.origin.file}:${q.origin.line}">${q.origin.file.split('/').slice(-2).join('/')}:${q.origin.line}</span>
                            <button onclick="copyToClipboard('${q.origin.file}:${q.origin.line}', event)" class="p-1 hover:bg-slate-700 rounded text-slate-500 hover:text-white transition-colors" title="Copy path">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                </svg>
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

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
            if (typeof resetWaterfall === 'function') {
                resetWaterfall();
            }
            document.getElementById('queries-info').textContent = 'Select a request to view queries';
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
