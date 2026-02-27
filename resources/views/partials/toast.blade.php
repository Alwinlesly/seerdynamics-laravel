<!-- Toast Notification Container -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; max-width: 400px;"></div>

<style>
.toast-notification {
    display: flex;
    align-items: flex-start;
    padding: 14px 16px;
    border-radius: 8px;
    min-width: 320px;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    animation: toastSlideIn 0.3s ease-out;
    position: relative;
}

.toast-notification.toast-hiding {
    animation: toastSlideOut 0.3s ease-in forwards;
}

.toast-notification .toast-icon {
    width: 22px;
    height: 22px;
    min-width: 22px;
    margin-right: 12px;
    margin-top: 2px;
}

.toast-notification .toast-body {
    flex: 1;
}

.toast-notification .toast-title {
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 2px;
}

.toast-notification .toast-message {
    font-size: 13px;
    opacity: 0.8;
}

.toast-notification .toast-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    margin-left: 12px;
    opacity: 0.6;
    line-height: 1;
}

.toast-notification .toast-close:hover {
    opacity: 1;
}

/* Success */
.toast-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

/* Error */
.toast-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Warning */
.toast-warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

/* Info */
.toast-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

@keyframes toastSlideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes toastSlideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>

<script>
function showToast(type, message, title) {
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    var titles = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Info'
    };

    var toastTitle = title || titles[type] || 'Notification';

    var toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.innerHTML = 
        '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
        '<div class="toast-body">' +
            '<div class="toast-title">' + toastTitle + '</div>' +
            '<div class="toast-message">' + message + '</div>' +
        '</div>' +
        '<button class="toast-close" onclick="closeToast(this)">×</button>';

    container.appendChild(toast);

    // Auto dismiss after 5 seconds
    setTimeout(function() {
        if (toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(function() {
                if (toast.parentNode) toast.remove();
            }, 300);
        }
    }, 5000);
}

function closeToast(btn) {
    var toast = btn.closest('.toast-notification');
    toast.classList.add('toast-hiding');
    setTimeout(function() {
        if (toast.parentNode) toast.remove();
    }, 300);
}
</script>
