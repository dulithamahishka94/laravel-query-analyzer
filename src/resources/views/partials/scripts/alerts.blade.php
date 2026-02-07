
    // ==================== Alerts ====================
    async function loadAlerts() {
        try {
            const res = await fetch('/query-lens/api/v2/alerts');
            const data = await res.json();

            renderAlerts(data.alerts || []);
        } catch (e) {
            console.error('Error loading alerts:', e);
        }
    }

    function getAlertIcon(type) {
        const icons = {
            slow_query: {
                color: 'text-rose-400 bg-rose-500/10 border-rose-500/20',
                path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
            },
            n_plus_one: {
                color: 'text-purple-400 bg-purple-500/10 border-purple-500/20',
                path: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
            },
            error_rate: {
                color: 'text-orange-400 bg-orange-500/10 border-orange-500/20',
                path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
            },
            threshold: {
                color: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
                path: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
            }
        };
        return icons[type] || {
            color: 'text-slate-400 bg-slate-500/10 border-slate-500/20',
            path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };
    }

    function getChannelIcon(channel) {
        const title = channel.charAt(0).toUpperCase() + channel.slice(1);
        
        switch (channel) {
            case 'mail':
                return `<svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="${title}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>`;
            case 'slack':
                return `<svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 24 24" title="${title}">
                    <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"></path>
                </svg>`;
            case 'log':
                return `<svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="${title}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>`;
            default:
                return `<svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="${title}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>`;
        }
    }

    function renderAlerts(alerts) {
        const container = document.getElementById('alerts-list');

        if (!alerts.length) {
            container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No alerts configured</div>';
            return;
        }

        container.innerHTML = alerts.map(a => {
            const icon = getAlertIcon(a.type);
            const channelsHtml = (a.channels || []).map(c => getChannelIcon(c)).join('');
            
            return `
            <div class="p-4 flex items-center justify-between hover:bg-slate-800/50 group transition-colors">
                <div class="flex items-start gap-4">
                    <div class="p-2 rounded-lg border ${icon.color}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon.path}"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-medium text-white">${escapeHtml(a.name)}</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold tracking-wide ${a.enabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-400 border border-slate-500/20'}">
                                ${a.enabled ? 'Active' : 'Disabled'}
                            </span>
                            <div class="flex items-center gap-1 ml-2 opacity-75">
                                ${channelsHtml}
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">Triggered <span class="${a.trigger_count > 0 ? 'text-slate-300 font-medium' : ''}">${a.trigger_count}</span> times</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="toggleAlert(${a.id})" class="p-2 rounded-lg bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors" title="${a.enabled ? 'Disable' : 'Enable'}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${a.enabled ? 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z' : 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z'}"></path>
                        </svg>
                    </button>
                    <button onclick="deleteAlert(${a.id})" class="p-2 rounded-lg bg-slate-800 border border-slate-700 hover:bg-rose-500/10 hover:border-rose-500/20 hover:text-rose-400 text-slate-400 transition-colors" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `}).join('');
    }

    async function loadAlertLogs() {
        try {
            const res = await fetch('/query-lens/api/v2/alerts/logs?hours=24&limit=20');
            const data = await res.json();

            const container = document.getElementById('alert-logs');
            const headerContainer = document.getElementById('alert-logs-header');
            
            // Render header with Clear button and Timestamp
            if (headerContainer) {
                const lastCleared = data.last_cleared_at ? new Date(data.last_cleared_at).toLocaleString() : 'Never';
                headerContainer.innerHTML = `
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="text-xs text-slate-500">
                            Last cleared: <span class="text-slate-400 font-medium">${lastCleared}</span>
                        </div>
                        <button onclick="clearAlertHistory()" class="text-xs text-rose-400 hover:text-rose-300 font-medium transition-colors flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Clear History
                        </button>
                    </div>
                `;
            }

            if (!data.logs?.length) {
                container.innerHTML = '<div class="p-4 text-center text-slate-500 text-sm">No recent alerts</div>';
                return;
            }

            container.innerHTML = data.logs.map(log => `
                <div class="p-3 hover:bg-slate-800/50 group border-b border-slate-800/50 last:border-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium text-white truncate max-w-[200px]">${escapeHtml(log.alert_name)}</span>
                                <span class="text-[10px] text-slate-500 whitespace-nowrap">${formatTime(new Date(log.created_at).getTime() / 1000)}</span>
                            </div>
                            <div class="text-xs text-slate-400 line-clamp-2 mb-2">${escapeHtml(log.message)}</div>
                            <button onclick='showTriggerDetails(${JSON.stringify(log).replace(/'/g, "&#39;")})' 
                                    class="text-[10px] px-2 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-400 rounded transition-colors inline-flex items-center gap-1">
                                More Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            console.error('Error loading alert logs:', e);
        }
    }


    function showCreateAlertModal() {
        document.getElementById('alert-modal').classList.remove('hidden');
        clearAlertFormErrors();
        updateThresholdLabel();
    }

    function hideAlertModal() {
        document.getElementById('alert-modal').classList.add('hidden');
        document.getElementById('alert-form').reset();
        clearAlertFormErrors();
        setAlertSubmitLoading(false);
    }

    function clearAlertFormErrors() {
        document.getElementById('alert-form-errors').classList.add('hidden');
        ['name', 'type', 'threshold', 'channels', 'cooldown'].forEach(field => {
            const errorEl = document.getElementById(`alert-${field}-error`);
            const inputEl = document.getElementById(`alert-${field}`);
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }
            if (inputEl) {
                inputEl.classList.remove('border-rose-500');
            }
        });
    }

    function showFieldError(field, message) {
        const errorEl = document.getElementById(`alert-${field}-error`);
        const inputEl = document.getElementById(`alert-${field}`);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        if (inputEl) {
            inputEl.classList.add('border-rose-500');
        }
    }

    function showFormError(message) {
        const errorBanner = document.getElementById('alert-form-errors');
        const errorText = document.getElementById('alert-form-error-text');
        errorText.textContent = message;
        errorBanner.classList.remove('hidden');
    }

    function setAlertSubmitLoading(loading) {
        const btn = document.getElementById('alert-submit-btn');
        const text = document.getElementById('alert-submit-text');
        const spinner = document.getElementById('alert-submit-spinner');

        btn.disabled = loading;
        text.textContent = loading ? 'Creating...' : 'Create Alert';
        spinner.classList.toggle('hidden', !loading);
    }

    function updateThresholdLabel() {
        const type = document.getElementById('alert-type').value;
        const label = document.getElementById('threshold-label');
        const unit = document.getElementById('threshold-unit');
        const hint = document.getElementById('threshold-hint');
        const input = document.getElementById('alert-threshold');
        const container = document.getElementById('threshold-container');

        switch (type) {
            case 'slow_query':
                label.textContent = 'Time Threshold';
                unit.textContent = 'sec';
                hint.textContent = 'Query execution time threshold (0.01 - 3600 seconds)';
                input.min = '0.01';
                input.max = '3600';
                input.step = '0.01';
                input.value = input.value || '1.0';
                container.classList.remove('hidden');
                break;
            case 'n_plus_one':
                label.textContent = 'Minimum Similar Queries';
                unit.textContent = 'count';
                hint.textContent = 'Minimum number of similar queries to trigger (1 - 100)';
                input.min = '1';
                input.max = '100';
                input.step = '1';
                input.value = '5';
                container.classList.remove('hidden');
                break;
            case 'threshold':
                label.textContent = 'Metric Threshold';
                unit.textContent = 'sec';
                hint.textContent = 'Custom metric threshold value';
                input.min = '0.01';
                input.max = '3600';
                input.step = '0.01';
                input.value = input.value || '1.0';
                container.classList.remove('hidden');
                break;
            case 'error_rate':
                label.textContent = 'Minimum Issues';
                unit.textContent = 'count';
                hint.textContent = 'Minimum number of issues to trigger (1 - 10)';
                input.min = '1';
                input.max = '10';
                input.step = '1';
                input.value = '1';
                container.classList.remove('hidden');
                break;
            default:
                container.classList.add('hidden');
        }
    }

    function validateAlertForm() {
        clearAlertFormErrors();
        let isValid = true;
        const errors = [];

        // Validate name
        const name = document.getElementById('alert-name').value.trim();
        if (!name) {
            showFieldError('name', 'Alert name is required');
            errors.push('Name is required');
            isValid = false;
        } else if (name.length < 3) {
            showFieldError('name', 'Alert name must be at least 3 characters');
            errors.push('Name too short');
            isValid = false;
        } else if (name.length > 255) {
            showFieldError('name', 'Alert name must be less than 255 characters');
            errors.push('Name too long');
            isValid = false;
        }

        // Validate type
        const type = document.getElementById('alert-type').value;
        if (!type) {
            showFieldError('type', 'Please select an alert type');
            errors.push('Type is required');
            isValid = false;
        } else if (!['slow_query', 'n_plus_one', 'threshold', 'error_rate'].includes(type)) {
            showFieldError('type', 'Invalid alert type selected');
            errors.push('Invalid type');
            isValid = false;
        }

        // Validate threshold
        const threshold = parseFloat(document.getElementById('alert-threshold').value);
        if (isNaN(threshold)) {
            showFieldError('threshold', 'Threshold must be a valid number');
            errors.push('Invalid threshold');
            isValid = false;
        } else if (threshold <= 0) {
            showFieldError('threshold', 'Threshold must be greater than 0');
            errors.push('Threshold must be positive');
            isValid = false;
        } else if (type === 'slow_query' && threshold > 3600) {
            showFieldError('threshold', 'Time threshold cannot exceed 3600 seconds');
            errors.push('Threshold too high');
            isValid = false;
        } else if (type === 'n_plus_one' && (threshold < 1 || threshold > 100 || !Number.isInteger(threshold))) {
            showFieldError('threshold', 'Must be a whole number between 1 and 100');
            errors.push('Invalid N+1 count');
            isValid = false;
        }

        // Validate channels
        const channels = document.querySelectorAll('input[name="channels[]"]:checked');
        if (channels.length === 0) {
            showFieldError('channels', 'Select at least one notification channel');
            errors.push('No channels selected');
            isValid = false;
        }

        // Validate cooldown
        const cooldown = parseInt(document.getElementById('alert-cooldown').value);
        if (isNaN(cooldown)) {
            showFieldError('cooldown', 'Cooldown must be a valid number');
            errors.push('Invalid cooldown');
            isValid = false;
        } else if (cooldown < 1 || cooldown > 1440) {
            showFieldError('cooldown', 'Cooldown must be between 1 and 1440 minutes');
            errors.push('Cooldown out of range');
            isValid = false;
        }

        if (!isValid) {
            showFormError(`Please fix ${errors.length} error${errors.length > 1 ? 's' : ''} before submitting`);
        }

        return isValid;
    }

    async function createAlert(e) {
        e.preventDefault();

        if (!validateAlertForm()) {
            return;
        }

        const form = e.target;
        const formData = new FormData(form);
        const type = formData.get('type');

        const channels = [];
        form.querySelectorAll('input[name="channels[]"]:checked').forEach(cb => channels.push(cb.value));

        // Build conditions based on type
        const threshold = parseFloat(formData.get('threshold'));
        const conditions = {};

        switch (type) {
            case 'slow_query':
                conditions.threshold = threshold;
                break;
            case 'n_plus_one':
                conditions.min_count = Math.floor(threshold);
                break;
            case 'threshold':
                conditions.threshold = threshold;
                conditions.metric = 'time';
                conditions.operator = '>=';
                break;
            case 'error_rate':
                conditions.min_issues = Math.floor(threshold);
                break;
        }

        setAlertSubmitLoading(true);

        try {
            const res = await fetch('/query-lens/api/v2/alerts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    name: formData.get('name').trim(),
                    type: type,
                    conditions: conditions,
                    channels: channels,
                    cooldown_minutes: parseInt(formData.get('cooldown_minutes'))
                })
            });

            const data = await res.json();

            if (res.ok) {
                hideAlertModal();
                loadAlerts();
                // Show success toast
                showToast('Alert created successfully', 'success');
            } else {
                // Handle server validation errors
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showFieldError(field, messages[0]);
                    });
                    showFormError('Server validation failed');
                } else if (data.message) {
                    showFormError(data.message);
                } else {
                    showFormError('Failed to create alert. Please try again.');
                }
            }
        } catch (err) {
            console.error('Error creating alert:', err);
            showFormError('Network error. Please check your connection and try again.');
        } finally {
            setAlertSubmitLoading(false);
        }
    }

    async function toggleAlert(id) {
        try {
            await fetch(`/query-lens/api/v2/alerts/${id}/toggle`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            loadAlerts();
        } catch (e) {
            console.error('Error toggling alert:', e);
        }
    }

    async function deleteAlert(id) {
        if (!confirm('Delete this alert?')) return;
        try {
            await fetch(`/query-lens/api/v2/alerts/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            loadAlerts();
        } catch (e) {
            console.error('Error deleting alert:', e);
        }
    }

    async function clearAlertHistory() {
        if (!confirm('Clear all alert history? Counters will be preserved.')) return;

        try {
            await fetch('/query-lens/api/v2/alerts/logs', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            loadAlertLogs();
            showToast('Alert history cleared', 'success');
        } catch (e) {
            console.error('Error clearing alert history:', e);
            showToast('Failed to clear history', 'error');
        }
    }

    function showTriggerDetails(log) {
        // Populate modal details
        document.getElementById('td-alert-name').textContent = log.alert_name;
        document.getElementById('td-timestamp').textContent = new Date(log.created_at).toLocaleString();
        document.getElementById('td-message').textContent = log.message;

        const contextContainer = document.getElementById('td-context');
        const context = log.context || {};
        
        // Build context details HTML
        let contextHtml = '';
        
        if (context.file) {
            contextHtml += `
                <div class="mb-3">
                    <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Location</div>
                    <div class="flex items-center justify-between bg-slate-900/50 p-2 rounded border border-slate-700 font-mono text-xs text-slate-300">
                        <span class="truncate" title="${escapeHtml(context.file)}:${context.line}">
                            ${escapeHtml(context.file.split('/').slice(-3).join('/'))}:${context.line}
                        </span>
                        <button onclick="copyToClipboard('${escapeHtml(context.file)}:${context.line}', event)" 
                                class="text-indigo-400 hover:text-white ml-2" title="Copy path">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }

        if (context.sql) {
            contextHtml += `
                <div class="mb-3">
                    <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Query</div>
                    <div class="bg-slate-900/50 p-2 rounded border border-slate-700 font-mono text-xs text-indigo-300 overflow-x-auto">
                        ${escapeHtml(context.sql)}
                    </div>
                </div>
            `;
        }
        
        // Additional metadata
        if (Object.keys(context).length > 2) {
             contextHtml += `<div class="text-[10px] uppercase font-bold text-slate-500 mb-1">Additional Info</div>`;
             contextHtml += `<div class="bg-slate-900/50 p-2 rounded border border-slate-700 text-xs text-slate-400 font-mono overflow-x-auto"><pre>${JSON.stringify(context, null, 2)}</pre></div>`;
        }

        contextContainer.innerHTML = contextHtml || '<div class="text-slate-500 text-sm italic">No additional context available</div>';

        // Show modal
        document.getElementById('trigger-details-modal').classList.remove('hidden');
    }

    function closeTriggerDetailsModal() {
        document.getElementById('trigger-details-modal').classList.add('hidden');
    }
