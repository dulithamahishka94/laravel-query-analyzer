
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
                const period = document.getElementById('period-select').value;
                const res = await fetch(`/query-lens/api/v2/poll?since=${state.lastPollTimestamp}&period=${period}&_cb=${Date.now()}`);
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

                // Poll returns same structure as overview: { today: {...}, comparison: {...} }
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
    function setStatValue(elementId, value, fallback = '-') {
        document.getElementById(elementId).textContent = value !== null ? value : fallback;
    }

    function setStatsToFallback() {
        ['stat-total', 'stat-slow', 'stat-avg', 'stat-p95'].forEach(id => setStatValue(id, null));
        ['header-total', 'header-slow', 'header-avg', 'header-p95'].forEach(id => setStatValue(id, null));
    }

    async function loadOverviewStats() {
        try {
            const period = document.getElementById('period-select').value;
            const res = await fetch(`/query-lens/api/v2/stats/overview?period=${period}`);
            const data = await res.json();

            const today = data.today || {};
            const comparison = data.comparison || {};
            const label = getPeriodLabel(period);

            const hasData = today.total_queries && today.total_queries > 0;

            setStatValue('stat-total', hasData ? formatNumber(today.total_queries) : null);
            setStatValue('stat-slow', hasData ? formatNumber(today.slow_queries || 0) : null);
            setStatValue('stat-avg', hasData ? formatMs(today.avg_time || 0) : null);
            setStatValue('stat-p95', hasData ? formatMs(today.p95_time || 0) : null);

            // Also update header stats from the overview response
            setStatValue('header-total', hasData ? formatNumber(today.total_queries) : null);
            setStatValue('header-slow', hasData ? formatNumber(today.slow_queries || 0) : null);
            setStatValue('header-avg', hasData ? formatMs(today.avg_time || 0) : null);
            setStatValue('header-p95', hasData ? formatMs(today.p95_time || 0) : null);

            if (hasData) {
                updateStatChange('stat-total-change', comparison.queries, label);
                updateStatChange('stat-slow-change', comparison.slow, label);
                updateStatChange('stat-avg-change', comparison.avg_time, label, true);
                updateStatChange('stat-p95-change', comparison.p95, label, true);
            }
        } catch (e) {
            console.error('Error loading overview:', e);
            setStatsToFallback();
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
        // stats is the overview structure: { today: {...}, comparison: {...} }
        const today = stats.today || {};
        const hasData = today.total_queries && today.total_queries > 0;
        setStatValue('header-total', hasData ? formatNumber(today.total_queries) : null);
        setStatValue('header-slow', hasData ? formatNumber(today.slow_queries || 0) : null);
        setStatValue('header-avg', hasData ? formatMs(today.avg_time || 0) : null);
        setStatValue('header-p95', hasData ? formatMs(today.p95_time || 0) : null);
    }
