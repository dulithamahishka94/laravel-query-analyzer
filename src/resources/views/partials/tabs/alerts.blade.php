<div id="tab-alerts" class="tab-content hidden h-full overflow-y-auto">
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
