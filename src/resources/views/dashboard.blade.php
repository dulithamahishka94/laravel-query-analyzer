<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Query Analyzer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #2d3748;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header .subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .status-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }

        .status-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .status-card.enabled h3 { color: #48bb78; }
        .status-card.total h3 { color: #4299e1; }
        .status-card.slow h3 { color: #f56565; }
        .status-card.avg h3 { color: #ed8936; }

        .controls {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #5a67d8;
        }

        .btn.danger {
            background: #f56565;
        }

        .btn.danger:hover {
            background: #e53e3e;
        }

        .btn.secondary {
            background: #718096;
        }

        .btn.secondary:hover {
            background: #4a5568;
        }

        select, input {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .queries-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .queries-header {
            background: #f7fafc;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }

        .query-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .query-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .query-item:hover {
            background: #f7fafc;
        }

        .query-item:last-child {
            border-bottom: none;
        }

        .query-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .query-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .query-type {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-weight: 600;
        }

        .query-type.SELECT { background: #bee3f8; color: #2b6cb0; }
        .query-type.INSERT { background: #c6f6d5; color: #276749; }
        .query-type.UPDATE { background: #fbb6ce; color: #97266d; }
        .query-type.DELETE { background: #fed7d7; color: #c53030; }

        .performance-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.7rem;
        }

        .performance-badge.fast { background: #c6f6d5; color: #276749; }
        .performance-badge.moderate { background: #faf089; color: #744210; }
        .performance-badge.slow { background: #fed7d7; color: #c53030; }
        .performance-badge.very_slow { background: #fc8181; color: #742a2a; }

        .query-sql {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85rem;
            color: #4a5568;
            background: #f7fafc;
            padding: 0.5rem;
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            max-width: 800px;
            max-height: 80%;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #718096;
        }

        .close:hover {
            color: #2d3748;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .status-bar {
                flex-direction: column;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .query-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laravel Query Analyzer</h1>
        <div class="subtitle">Real-time database query analysis and optimization insights</div>
    </div>

    <div class="container">
        <div class="status-bar">
            <div class="status-card enabled">
                <h3>{{ $isEnabled ? 'ON' : 'OFF' }}</h3>
                <p>Analyzer Status</p>
            </div>
            <div class="status-card total">
                <h3 id="total-queries">{{ $stats['total_queries'] }}</h3>
                <p>Total Queries</p>
            </div>
            <div class="status-card slow">
                <h3 id="slow-queries">{{ $stats['slow_queries'] }}</h3>
                <p>Slow Queries</p>
            </div>
            <div class="status-card avg">
                <h3 id="avg-time">{{ number_format($stats['average_time'], 3) }}s</h3>
                <p>Average Time</p>
            </div>
        </div>

        <div class="controls">
            <button class="btn" onclick="refreshQueries()">Refresh</button>
            <button class="btn danger" onclick="resetQueries()">Reset Queries</button>
            <button class="btn secondary" onclick="exportQueries('json')">Export JSON</button>
            <button class="btn secondary" onclick="exportQueries('csv')">Export CSV</button>

            <select id="type-filter" onchange="filterQueries()">
                <option value="all">All Types</option>
                <option value="select">SELECT</option>
                <option value="insert">INSERT</option>
                <option value="update">UPDATE</option>
                <option value="delete">DELETE</option>
            </select>

            <label>
                <input type="checkbox" id="slow-only" onchange="filterQueries()"> Show slow queries only
            </label>

            <label>
                Auto-refresh: <input type="checkbox" id="auto-refresh" onchange="toggleAutoRefresh()">
            </label>
        </div>

        <div class="queries-container">
            <div class="queries-header">
                Database Queries (<span id="query-count">0</span>)
            </div>
            <div id="query-list" class="query-list">
                <div class="loading">Loading queries...</div>
            </div>
        </div>
    </div>

    <!-- Query Details Modal -->
    <div id="query-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Query Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener('DOMContentLoaded', function() {
            refreshQueries();
        });

        async function refreshQueries() {
            try {
                const typeFilter = document.getElementById('type-filter').value;
                const slowOnly = document.getElementById('slow-only').checked;

                const params = new URLSearchParams();
                if (typeFilter !== 'all') params.append('type', typeFilter);
                if (slowOnly) params.append('slow_only', '1');

                const response = await fetch(`/query-analyzer/api/queries?${params}`);
                const data = await response.json();

                updateStats(data.stats);
                renderQueries(data.queries);
            } catch (error) {
                console.error('Error fetching queries:', error);
                document.getElementById('query-list').innerHTML =
                    '<div class="empty-state">Error loading queries. Please try again.</div>';
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
                    <div class="empty-state">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"></path>
                        </svg>
                        <h3>No queries found</h3>
                        <p>Execute some database queries to see them here</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = queries.map((query, index) => `
                <div class="query-item" onclick="showQueryDetails(${index})">
                    <div class="query-header">
                        <div class="query-meta">
                            <span class="query-type ${query.analysis.type}">${query.analysis.type}</span>
                            <span class="performance-badge ${query.analysis.performance.rating}">
                                ${query.time.toFixed(3)}s
                            </span>
                            <span>Complexity: ${query.analysis.complexity.level}</span>
                            ${query.analysis.issues.length > 0 ? `<span style="color: #f56565;">⚠ ${query.analysis.issues.length} issues</span>` : ''}
                        </div>
                    </div>
                    <div class="query-sql">${escapeHtml(query.sql.substring(0, 200))}${query.sql.length > 200 ? '...' : ''}</div>
                </div>
            `).join('');
        }

        async function showQueryDetails(index) {
            try {
                const response = await fetch(`/query-analyzer/api/query/${index}`);
                const query = await response.json();

                document.getElementById('modal-body').innerHTML = `
                    <div style="margin-bottom: 1rem;">
                        <h3>Query Information</h3>
                        <p><strong>Type:</strong> ${query.analysis.type}</p>
                        <p><strong>Execution Time:</strong> ${query.time.toFixed(3)}s</p>
                        <p><strong>Performance Rating:</strong> ${query.analysis.performance.rating}</p>
                        <p><strong>Complexity:</strong> ${query.analysis.complexity.level} (Score: ${query.analysis.complexity.score})</p>
                        <p><strong>Connection:</strong> ${query.connection}</p>
                        <p><strong>Timestamp:</strong> ${new Date(query.timestamp * 1000).toLocaleString()}</p>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <h3>SQL Query</h3>
                        <pre style="background: #f7fafc; padding: 1rem; border-radius: 4px; overflow-x: auto;">${escapeHtml(query.sql)}</pre>
                    </div>

                    ${query.bindings && query.bindings.length > 0 ? `
                    <div style="margin-bottom: 1rem;">
                        <h3>Bindings</h3>
                        <pre style="background: #f7fafc; padding: 1rem; border-radius: 4px;">${JSON.stringify(query.bindings, null, 2)}</pre>
                    </div>
                    ` : ''}

                    ${query.analysis.recommendations.length > 0 ? `
                    <div style="margin-bottom: 1rem;">
                        <h3>Recommendations</h3>
                        <ul style="margin-left: 1rem;">
                            ${query.analysis.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                    ` : ''}

                    ${query.analysis.issues.length > 0 ? `
                    <div style="margin-bottom: 1rem;">
                        <h3>Issues</h3>
                        <ul style="margin-left: 1rem; color: #f56565;">
                            ${query.analysis.issues.map(issue => `<li><strong>${issue.type}:</strong> ${issue.message}</li>`).join('')}
                        </ul>
                    </div>
                    ` : ''}

                    <div>
                        <h3>Complexity Breakdown</h3>
                        <p>JOINs: ${query.analysis.complexity.joins}</p>
                        <p>Subqueries: ${query.analysis.complexity.subqueries}</p>
                        <p>Conditions: ${query.analysis.complexity.conditions}</p>
                    </div>
                `;

                document.getElementById('query-modal').style.display = 'block';
            } catch (error) {
                console.error('Error fetching query details:', error);
            }
        }

        function closeModal() {
            document.getElementById('query-modal').style.display = 'none';
        }

        function filterQueries() {
            refreshQueries();
        }

        async function resetQueries() {
            if (!confirm('Are you sure you want to reset all queries?')) return;

            try {
                await fetch('/query-analyzer/api/reset', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
                refreshQueries();
            } catch (error) {
                console.error('Error resetting queries:', error);
            }
        }

        async function exportQueries(format) {
            try {
                const response = await fetch('/query-analyzer/api/export', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ format })
                });

                const result = await response.json();

                const blob = new Blob([result.data], {
                    type: format === 'csv' ? 'text/csv' : 'application/json'
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = result.filename;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting queries:', error);
            }
        }

        function toggleAutoRefresh() {
            const checkbox = document.getElementById('auto-refresh');

            if (checkbox.checked) {
                autoRefreshInterval = setInterval(refreshQueries, 5000); // Refresh every 5 seconds
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('query-modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>