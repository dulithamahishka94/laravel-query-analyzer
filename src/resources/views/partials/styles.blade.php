<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                    mono: ['JetBrains Mono', 'monospace'],
                }
            }
        }
    }
</script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .text-xs { font-size: 0.90rem !important; }

        /* Markdown Content Styles */
        .markdown-content ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 0.5em; }
        .markdown-content ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 0.5em; }
        .markdown-content p { margin-bottom: 0.75em; }
        .markdown-content strong { font-weight: 600; color: #818cf8; }
        .markdown-content code { background-color: #1e293b; padding: 0.1em 0.3em; border-radius: 0.2em; font-family: 'JetBrains Mono', monospace; font-size: 0.9em; color: #f472b6; }
        .markdown-content pre { background-color: #0f172a; color: #e2e8f0; padding: 1em; border-radius: 0.5em; overflow-x: auto; margin-bottom: 1em; border: 1px solid #334155; }
        .markdown-content pre code { background-color: transparent; color: inherit; padding: 0; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }

        /* Card styles */
        .card { @apply bg-slate-800/50 rounded-xl border border-slate-700/50 backdrop-blur-sm; }
        .card-header { @apply px-4 py-3 border-b border-slate-700/50 flex items-center justify-between; }
        .card-title { @apply text-xs font-semibold text-slate-400 uppercase tracking-wider; }
        .card-body { @apply p-4; }

        /* Stat card */
        .stat-card { @apply bg-gradient-to-br from-slate-800/80 to-slate-900/80 rounded-xl border border-slate-700/50 p-4 backdrop-blur-sm transition-all hover:border-slate-600/50; }
        .stat-value { @apply text-2xl font-bold text-white tabular-nums; }
        .stat-label { @apply text-xs text-slate-500 uppercase tracking-wider mt-1; }
        .stat-change { @apply text-xs font-medium mt-2 flex items-center gap-1; }
        .stat-change.up { @apply text-emerald-400; }
        .stat-change.down { @apply text-rose-400; }
        .stat-change.neutral { @apply text-slate-500; }

        /* Query card */
        .query-card { @apply bg-slate-800/30 rounded-lg border border-slate-700/30 p-3 cursor-pointer transition-all hover:bg-slate-800/50 hover:border-slate-600/50; }
        .query-card.selected { @apply bg-indigo-900/30 border-indigo-500/50; }

        /* Type badges */
        .badge { @apply px-2 py-0.5 rounded text-xs font-semibold; }
        .badge-select { @apply bg-blue-500/20 text-blue-400 border border-blue-500/30; }
        .badge-insert { @apply bg-emerald-500/20 text-emerald-400 border border-emerald-500/30; }
        .badge-update { @apply bg-amber-500/20 text-amber-400 border border-amber-500/30; }
        .badge-delete { @apply bg-rose-500/20 text-rose-400 border border-rose-500/30; }
        .badge-cache { @apply bg-purple-500/20 text-purple-400 border border-purple-500/30; }

        /* Source badges - App/Vendor */
        .badge-source {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        .badge-app {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.1);
            animation: app-glow 3s ease-in-out infinite;
        }
        .badge-app:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.2) 100%);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }
        @keyframes app-glow {
            0%, 100% { box-shadow: 0 0 8px rgba(16, 185, 129, 0.1); }
            50% { box-shadow: 0 0 12px rgba(16, 185, 129, 0.2); }
        }
        .badge-vendor {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.2) 0%, rgba(71, 85, 105, 0.15) 100%);
            color: #94a3b8;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }
        .badge-vendor:hover {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.3) 0%, rgba(71, 85, 105, 0.25) 100%);
            border-color: rgba(100, 116, 139, 0.5);
            transform: translateY(-1px);
        }

        /* Issue badges */
        .badge-issue {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .badge-issue:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }
        .badge-n-plus-one {
            background: rgba(168, 85, 247, 0.15);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        .badge-n-plus-one:hover {
            background: rgba(168, 85, 247, 0.25);
            box-shadow: 0 0 8px rgba(168, 85, 247, 0.2);
        }
        .badge-slow-query {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }
        .badge-slow-query:hover {
            background: rgba(244, 63, 94, 0.25);
            box-shadow: 0 0 8px rgba(244, 63, 94, 0.2);
        }
        .badge-security {
            background: rgba(251, 146, 60, 0.15);
            color: #fb923c;
            border: 1px solid rgba(251, 146, 60, 0.3);
        }
        .badge-security:hover {
            background: rgba(251, 146, 60, 0.25);
            box-shadow: 0 0 8px rgba(251, 146, 60, 0.2);
        }
        .badge-performance {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .badge-performance:hover {
            background: rgba(251, 191, 36, 0.25);
            box-shadow: 0 0 8px rgba(251, 191, 36, 0.2);
        }

        /* Performance badges */
        .perf-fast { @apply bg-emerald-500/20 text-emerald-400; }
        .perf-moderate { @apply bg-amber-500/20 text-amber-400; }
        .perf-slow { @apply bg-orange-500/20 text-orange-400; }
        .perf-very_slow { @apply bg-rose-500/20 text-rose-400; }

        /* Pulse animation for live indicator */
        .pulse-live {
            animation: pulse-live 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-live {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Sparkline container */
        .sparkline { height: 30px; }

        /* Tab styles - Modern pill design */
        .tabs-container {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 12px;
        }
        .tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .tab:hover {
            color: #e2e8f0;
            background: rgba(51, 65, 85, 0.5);
        }
        .tab.active {
            color: #ffffff;
            background: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .tab .tab-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* Alert item */
        .alert-item { @apply p-3 rounded-lg border border-slate-700/50 bg-slate-800/30; }
        .alert-item.critical { @apply border-l-4 border-l-rose-500; }
        .alert-item.warning { @apply border-l-4 border-l-amber-500; }

        /* Select/Dropdown styling fixes */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        select option {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 8px 12px;
        }
        select:focus {
            outline: none;
            ring: 2px;
            ring-color: #6366f1;
            border-color: #6366f1;
        }

        /* Ensure dropdowns appear above other content */
        .filters-section select,
        .card-header select {
            position: relative;
            z-index: 10;
        }

        /* Fix overflow issues - filters visible, request list scrollable */
        #request-list {
            overflow-y: auto;
            min-height: 0;
        }

        /* Details/Summary styling for collapsible sections */
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary {
            list-style: none;
        }
    </style>
