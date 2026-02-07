
    // ==================== Initialization ====================
    document.addEventListener('DOMContentLoaded', async () => {
        updateGranularityButtons();
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
            const period = document.getElementById('period-select').value;
            const res = await fetch(`/query-lens/api/v2/stats/overview?period=${period}`);
            const data = await res.json();

            const today = data.today || {};
            const comparison = data.comparison || {};
            const label = getPeriodLabel(period);

            document.getElementById('stat-total').textContent = formatNumber(today.total_queries || 0);
            document.getElementById('stat-slow').textContent = formatNumber(today.slow_queries || 0);
            document.getElementById('stat-avg').textContent = formatMs(today.avg_time || 0);
            document.getElementById('stat-p95').textContent = formatMs(today.p95_time || 0);

            updateStatChange('stat-total-change', comparison.queries, label);
            updateStatChange('stat-slow-change', comparison.slow, label);
            updateStatChange('stat-avg-change', comparison.avg_time, label, true);
            updateStatChange('stat-p95-change', comparison.p95, label, true);
        } catch (e) {
            console.error('Error loading overview:', e);
        }
    }

    function getPeriodLabel(period) {
        switch(period) {
            case '1h': return 'vs last hour';
            case '7d': return 'vs last week';
            case '30d': return 'vs last month';
            case '24h':
            default: return 'vs yesterday';
        }
    }

    function updateStatChange(elementId, change, label, invertGood = false) {
        const el = document.getElementById(elementId);
        if (!change) return;

        const icon = change.direction === 'up' ? '&uarr;' : change.direction === 'down' ? '&darr;' : '';
        const isGood = invertGood ? change.direction === 'down' : change.direction !== 'up';

        el.innerHTML = `<span>${icon} ${change.value}%</span> <span class="text-slate-500">${label}</span>`;
        el.className = `stat-change ${isGood ? (change.direction === 'neutral' ? 'neutral' : 'down') : 'up'}`;
    }

    function updateHeaderStats(stats) {
        if (!stats) return;
        document.getElementById('header-total').textContent = formatNumber(stats.total_queries || 0);
        document.getElementById('header-slow').textContent = formatNumber(stats.slow_queries || 0);
        document.getElementById('header-avg').textContent = formatMs(stats.average_time || 0);
    }
