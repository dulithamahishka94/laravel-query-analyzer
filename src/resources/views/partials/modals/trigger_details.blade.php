<div id="trigger-details-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeTriggerDetailsModal()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto" onclick="if(event.target === this) closeTriggerDetailsModal()">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0" onclick="if(event.target === this) closeTriggerDetailsModal()">
            <div class="relative transform overflow-hidden rounded-lg bg-slate-800 border border-slate-700 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                <!-- Header -->
                <div class="bg-slate-900/50 px-4 py-3 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white" id="modal-title">Alert Trigger Details</h3>
                    <button type="button" onclick="closeTriggerDetailsModal()" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-4 py-4 space-y-4">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Alert Name</div>
                            <div class="text-sm font-medium text-white" id="td-alert-name">-</div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Time</div>
                            <div class="text-sm text-slate-300 font-mono" id="td-timestamp">-</div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Message</div>
                        <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-lg text-rose-300 text-xs" id="td-message">
                            -
                        </div>
                    </div>

                    <!-- Context (Dynamic) -->
                    <div id="td-context" class="space-y-3">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-slate-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" onclick="closeTriggerDetailsModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-slate-600 hover:bg-slate-600 sm:mt-0 sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
