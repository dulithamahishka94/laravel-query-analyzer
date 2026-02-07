
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
