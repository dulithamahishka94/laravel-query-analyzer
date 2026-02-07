
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
                { name: 'Avg Latency', type: 'line', data: data.latency },
                { name: 'P95 Latency', type: 'line', data: data.p95 },
                { name: 'Throughput', type: 'line', data: data.throughput }
            ],
            chart: {
                type: 'line',
                height: 300,
                toolbar: { show: false },
                background: 'transparent',
                animations: { enabled: false }
            },
            colors: ['#818cf8', '#f59e0b', '#ef4444'],
            stroke: { curve: 'smooth', width: 2 },
            markers: { size: 4, hover: { size: 6 }, strokeWidth: 0 },
            xaxis: {
                categories: data.labels || [],
                labels: { style: { colors: '#64748b' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    seriesName: 'Avg Latency',
                    labels: {
                        style: { colors: '#818cf8' },
                        formatter: v => v ? v.toFixed(1) + 'ms' : '0ms'
                    },
                    title: {
                        text: 'Latency (ms)',
                        style: { color: '#818cf8', fontSize: '12px' }
                    }
                },
                {
                    seriesName: 'P95 Latency',
                    show: false
                },
                {
                    opposite: true,
                    seriesName: 'Throughput',
                    labels: {
                        style: { colors: '#ef4444' },
                        formatter: v => v ? Math.round(v) : '0'
                    },
                    title: {
                        text: 'Queries',
                        style: { color: '#ef4444', fontSize: '12px' }
                    }
                }
            ],
            grid: { borderColor: '#334155', strokeDashArray: 4 },
            legend: { labels: { colors: '#94a3b8' } },
            tooltip: {
                theme: 'dark',
                y: [
                    { formatter: v => v ? v.toFixed(2) + 'ms' : '0ms' },
                    { formatter: v => v ? v.toFixed(2) + 'ms' : '0ms' },
                    { formatter: v => v ? Math.round(v) : '0' }
                ]
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
        updateGranularityButtons();
        loadTrendsChart();
    }

    function updateGranularityButtons() {
        document.querySelectorAll('.granularity-btn').forEach(btn => {
            const isActive = btn.dataset.granularity === state.granularity;
            btn.className = isActive
                ? 'granularity-btn px-3 py-1 text-xs rounded bg-indigo-500/20 text-indigo-400 font-semibold'
                : 'granularity-btn px-3 py-1 text-xs rounded bg-slate-800 text-slate-400 hover:bg-slate-700';
        });
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
    function resetWaterfall() {
        document.getElementById('waterfall-stats').classList.add('hidden');
        document.getElementById('waterfall-legend').classList.add('hidden');
        document.getElementById('waterfall-chart').innerHTML = `
            <div class="flex items-center justify-center h-64 text-slate-500">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    <p>Select a request from the sidebar</p>
                </div>
            </div>
        `;
        document.getElementById('waterfall-info').textContent = 'Select a request to view timeline';
    }
