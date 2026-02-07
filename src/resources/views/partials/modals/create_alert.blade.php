<div id="alert-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center">
    <div class="bg-slate-800 rounded-xl border border-slate-700 w-full max-w-md mx-4 shadow-2xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-700">
            <h3 class="text-sm font-semibold text-white">Create Alert</h3>
            <button type="button" onclick="hideAlertModal()" class="p-1 rounded hover:bg-slate-700 text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <!-- Error Banner -->
        <div id="alert-form-errors" class="hidden px-4 py-2 bg-rose-500/10 border-b border-rose-500/20">
            <div class="flex items-center gap-2 text-rose-400 text-xs">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span id="alert-form-error-text">Please fix the errors below</span>
            </div>
        </div>
        <form id="alert-form" onsubmit="createAlert(event)" class="p-4 space-y-4" novalidate>
            <!-- Alert Name -->
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">
                    Alert Name <span class="text-rose-400">*</span>
                </label>
                <input type="text" name="name" id="alert-name"
                       class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors"
                       placeholder="e.g., Slow Query Alert"
                       minlength="3"
                       maxlength="255"
                       required>
                <p id="alert-name-error" class="hidden mt-1 text-xs text-rose-400"></p>
                <p class="mt-1 text-[10px] text-slate-500">3-255 characters, descriptive name for this alert</p>
            </div>

            <!-- Alert Type -->
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">
                    Alert Type <span class="text-rose-400">*</span>
                </label>
                <select name="type" id="alert-type" required
                        onchange="updateThresholdLabel()"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">Select alert type...</option>
                    <option value="slow_query">Slow Query - Triggers when a single query exceeds time threshold</option>
                    <option value="n_plus_one">N+1 Detection - Triggers when N+1 query pattern detected</option>
                    <option value="threshold">Threshold - Triggers when metric exceeds custom threshold</option>
                    <option value="error_rate">Error Rate - Triggers when query has issues/warnings</option>
                </select>
                <p id="alert-type-error" class="hidden mt-1 text-xs text-rose-400"></p>
            </div>

            <!-- Threshold -->
            <div id="threshold-container">
                <label class="block text-xs font-medium text-slate-400 mb-1">
                    <span id="threshold-label">Threshold (seconds)</span> <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="threshold" id="alert-threshold"
                           step="0.01"
                           min="0.01"
                           max="3600"
                           value="1.0"
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 pr-12 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                           required>
                    <span id="threshold-unit" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">sec</span>
                </div>
                <p id="alert-threshold-error" class="hidden mt-1 text-xs text-rose-400"></p>
                <p id="threshold-hint" class="mt-1 text-[10px] text-slate-500">Query execution time threshold (0.01 - 3600 seconds)</p>
            </div>

            <!-- Notification Channels -->
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">
                    Notification Channels <span class="text-rose-400">*</span>
                </label>
                <div class="flex flex-wrap gap-4 mt-2">
                    <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" name="channels[]" value="log" checked
                               class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Log
                        </span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" name="channels[]" value="mail"
                               class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Email
                        </span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer hover:text-white transition-colors">
                        <input type="checkbox" name="channels[]" value="slack"
                               class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                            </svg>
                            Slack
                        </span>
                    </label>
                </div>
                <p id="alert-channels-error" class="hidden mt-1 text-xs text-rose-400"></p>
                <p class="mt-1 text-[10px] text-slate-500">Select at least one notification channel</p>
            </div>

            <!-- Cooldown -->
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">
                    Cooldown (minutes) <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="cooldown_minutes" id="alert-cooldown"
                           value="5"
                           min="1"
                           max="1440"
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 pr-12 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                           required>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">min</span>
                </div>
                <p id="alert-cooldown-error" class="hidden mt-1 text-xs text-rose-400"></p>
                <p class="mt-1 text-[10px] text-slate-500">Minimum time between consecutive triggers (1-1440 minutes)</p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-700">
                <button type="button" onclick="hideAlertModal()" class="px-4 py-2 text-sm text-slate-400 hover:text-white transition-colors">
                    Cancel
                </button>
                <button type="submit" id="alert-submit-btn"
                        class="px-4 py-2 bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span id="alert-submit-text">Create Alert</span>
                    <svg id="alert-submit-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
