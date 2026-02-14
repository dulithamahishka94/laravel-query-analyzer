<div id="tab-alerts" class="tab-content hidden h-full overflow-y-auto">
    @if(!config('query-lens.alerts.enabled'))
        <div class="mb-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h3 class="text-sm font-medium text-yellow-500">Alerts are disabled</h3>
                <p class="text-xs text-slate-400 mt-1">Alerts are currently turned off in your configuration (QUERY_LENS_ALERTS=false). No new alerts will be triggered.</p>
            </div>
        </div>
    @endif
    <div class="grid grid-cols-3 gap-4">
        <!-- Alert Configuration -->
        <div class="col-span-2 card">
            <div class="card-header !pb-4 gap-4 flex items-center justify-between">
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
                <div id="alert-logs-header" class="border-b border-slate-700/50 p-2"></div>
                <div id="alert-logs" class="divide-y divide-slate-700/50 max-h-[400px] overflow-y-auto">
                    <div class="p-4 text-center text-slate-500 text-sm">No recent alerts</div>
                </div>
            </div>
        </div>
    </div>
</div>
