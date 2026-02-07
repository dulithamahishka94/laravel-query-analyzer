<div id="detail-panel" class="hidden w-[500px] bg-slate-900 border-l border-slate-800 flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
        <h3 class="text-sm font-semibold text-white">Query Details</h3>
        <button onclick="closeDetails()" class="p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <div id="detail-content" class="flex-1 overflow-y-auto p-4">
        <!-- Content populated by JS -->
    </div>
</div>
