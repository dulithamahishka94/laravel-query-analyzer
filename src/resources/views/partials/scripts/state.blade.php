// ==================== State ====================
    const state = {
        currentRequestId: null,
        currentQueries: [],
        currentTab: 'trends',
        granularity: 'minute',
        pollInterval: {{ config('query-lens.dashboard.poll_interval', 5000) }},
        lastPollTimestamp: 0,
        charts: {
            trends: null,
            waterfall: null
        }
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
