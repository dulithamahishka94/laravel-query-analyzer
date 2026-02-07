
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

        document.getElementById('detail-content').innerHTML = `
            <div class="space-y-6">
                <!-- Header with badges -->
                <div class="flex flex-wrap items-center gap-2 pb-2 border-b border-slate-800/50">
                    <span class="badge badge-${(analysis.type || 'other').toLowerCase()}">${analysis.type || 'QUERY'}</span>
                    ${isVendor
                        ? '<span class="badge-source badge-vendor"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Vendor</span>'
                        : '<span class="badge-source badge-app"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>App</span>'
                    }
                    ${isSlow ? '<span class="badge-issue badge-slow-query"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Slow</span>' : ''}
                    ${isNPlusOne ? '<span class="badge-issue badge-n-plus-one"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>N+1</span>' : ''}
                    <span class="badge perf-${analysis.performance?.rating || 'fast'} ml-auto">${(query.time * 1000).toFixed(3)}ms</span>
                </div>

                <!-- SQL Section -->
                <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/30">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SQL Statement</h3>
                        ${analysis.type === 'SELECT' ? `
                            <button onclick="runExplain('${query.id}')" class="px-2 py-1 bg-indigo-500/20 text-indigo-400 text-[10px] font-semibold rounded hover:bg-indigo-500/30 transition-colors border border-indigo-500/20">
                                RUN EXPLAIN
                            </button>
                        ` : ''}
                    </div>
                    <div class="p-4">
                        <pre class="p-3 bg-slate-900 rounded-lg text-xs font-mono text-slate-300 overflow-x-auto whitespace-pre-wrap border border-slate-800">${escapeHtml(query.sql)}</pre>
                        <div id="explain-result-${query.id}" class="hidden mt-4 pt-4 border-t border-slate-700/50"></div>
                    </div>
                </div>

                <!-- Bindings -->
                ${query.bindings && query.bindings.length ? `
                    <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">
                        <div class="px-4 py-2 border-b border-slate-700/50 bg-slate-800/30">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bindings (${query.bindings.length})</h3>
                        </div>
                        <div class="p-4">
                            <div class="p-3 bg-slate-900 rounded-lg text-xs font-mono text-slate-300 overflow-x-auto border border-slate-800">
                                [${query.bindings.map(b => typeof b === 'string' ? `"${escapeHtml(b)}"` : b).join(', ')}]
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Performance & Context Grid -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 p-3 text-center">
                        <label class="text-[10px] text-slate-500 uppercase font-semibold">Connection</label>
                        <div class="text-sm font-medium text-white mt-1">${query.connection || 'default'}</div>
                    </div>
                    <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 p-3 text-center">
                        <label class="text-[10px] text-slate-500 uppercase font-semibold">Complexity</label>
                        <div class="text-sm font-medium text-white mt-1">${analysis.complexity?.level || 'N/A'} <span class="text-slate-500 text-xs">(${analysis.complexity?.score || 0})</span></div>
                    </div>
                    <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 p-3 text-center">
                        <label class="text-[10px] text-slate-500 uppercase font-semibold">Performance</label>
                        <div class="text-sm font-medium mt-1 ${isSlow ? 'text-rose-400' : 'text-emerald-400'}">${analysis.performance?.rating || 'fast'}</div>
                    </div>
                </div>

                <!-- Origin -->
                ${origin.file ? `
                    <div class="bg-slate-800/40 rounded-xl border border-slate-700/50 overflow-hidden">
                        <div class="px-4 py-2 border-b border-slate-700/50 bg-slate-800/30">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Origin</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                ${isVendor
                                    ? '<span class="badge-source badge-vendor"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>Vendor Package</span>'
                                    : '<span class="badge-source badge-app"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>Application Code</span>'
                                }
                            </div>
                            <div class="p-3 bg-slate-900 rounded-lg font-mono text-xs ${isVendor ? 'text-slate-500' : 'text-indigo-400'} break-all border border-slate-800">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                                    <span class="leading-relaxed">${origin.file}:${origin.line}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Issues -->
                ${issues.length ? `
                    <div class="bg-rose-500/5 rounded-xl border border-rose-500/20 overflow-hidden">
                        <div class="px-4 py-2 border-b border-rose-500/20 bg-rose-500/10">
                            <h3 class="text-xs font-semibold text-rose-400 uppercase tracking-wider">Issues Detected (${issues.length})</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            ${issues.map(i => {
                                const issueStyles = {
                                    'n+1': { bg: 'rgba(168, 85, 247, 0.1)', border: 'rgba(168, 85, 247, 0.2)', text: '#c084fc', label: '#a855f7' },
                                    'security': { bg: 'rgba(249, 115, 22, 0.1)', border: 'rgba(249, 115, 22, 0.2)', text: '#fdba74', label: '#f97316' },
                                    'performance': { bg: 'rgba(245, 158, 11, 0.1)', border: 'rgba(245, 158, 11, 0.2)', text: '#fcd34d', label: '#f59e0b' },
                                    'default': { bg: 'rgba(244, 63, 94, 0.1)', border: 'rgba(244, 63, 94, 0.2)', text: '#fda4af', label: '#f43f5e' }
                                };
                                const style = issueStyles[i.type?.toLowerCase()] || issueStyles.default;
                                return `
                                    <div class="p-3 rounded-lg flex gap-3" style="background: ${style.bg}; border: 1px solid ${style.border}">
                                        <div class="flex-shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" style="color: ${style.label}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider block mb-1" style="color: ${style.label}">${escapeHtml(i.type)}</span>
                                            <div class="text-xs leading-relaxed" style="color: ${style.text}">${escapeHtml(i.message)}</div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : ''}

                <!-- Recommendations -->
                ${recommendations.length ? `
                    <div class="bg-indigo-500/5 rounded-xl border border-indigo-500/10 overflow-hidden">
                        <div class="px-4 py-2 border-b border-indigo-500/10 bg-indigo-500/10">
                            <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Recommendations</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            ${recommendations.map(r => `
                                <div class="flex gap-3 p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-lg">
                                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-xs text-indigo-300 leading-relaxed">${r}</span>
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
                            <div class="p-3 bg-black/50 rounded-lg text-sm text-slate-300 overflow-x-auto leading-relaxed border border-slate-700/50 markdown-content">
                                ${marked.parse(humanizedExplain)}
                            </div>
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
                            <pre class="mt-2 p-3 bg-black/30 rounded text-sm font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap selection:bg-indigo-500 selection:text-white">${escapeHtml(data.raw_analyze)}</pre>
                        </details>
                    ` : ''}
                </div>
            `;
        } catch (e) {
            container.innerHTML = `<div class="p-3 bg-rose-500/10 rounded-lg text-rose-400 text-xs">Error: ${e.message}</div>`;
        }
    }
