
    // ==================== Utilities ====================
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-indigo-500';
        toast.className = `fixed bottom-4 right-4 px-4 py-2 ${bgColor} text-white text-sm rounded-lg shadow-lg z-50 animate-fade-in`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showTopQueryDetail(sql) {
        alert(sql); // Simple preview, could be modal
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatNumber(n) {
        return new Intl.NumberFormat().format(n);
    }

    function formatMs(seconds) {
        return (seconds * 1000).toFixed(1) + 'ms';
    }

    function formatTime(ts) {
        return new Date(ts * 1000).toLocaleTimeString();
    }

    function getTypeColor(type) {
        const colors = {
            SELECT: '#3b82f6',
            INSERT: '#10b981',
            UPDATE: '#f59e0b',
            DELETE: '#ef4444',
            CACHE: '#8b5cf6',
            OTHER: '#8b5cf6'
        };
        return colors[type?.toUpperCase()] || '#64748b';
    }

    function getPeriodDates() {
        const period = document.getElementById('period-select').value;
        const end = new Date().toISOString();
        let start;

        switch (period) {
            case '1h': start = new Date(Date.now() - 3600000).toISOString(); break;
            case '7d': start = new Date(Date.now() - 7 * 86400000).toISOString(); break;
            default: start = new Date(Date.now() - 86400000).toISOString();
        }

        return { start, end };
    }

    function onPeriodChange() {
        // Refresh all data that depends on the selected period
        loadTrendsChart();
        loadTopQueries('slowest');
        loadTopQueries('most_frequent');
        loadOverviewStats();
    }

    async function copyToClipboard(text, event) {
        if (event) event.stopPropagation();
        
        // Fallback for non-secure contexts (http)
        if (!navigator.clipboard) {
            try {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";  // Avoid scrolling to bottom
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    showToast('Copied to clipboard', 'success');
                } else {
                    showToast('Failed to copy', 'error');
                }
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                showToast('Failed to copy', 'error');
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            showToast('Copied to clipboard', 'success');
        } catch (err) {
            console.error('Failed to copy code: ', err);
            showToast('Failed to copy', 'error');
        }
    }
