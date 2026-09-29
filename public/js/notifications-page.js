document.addEventListener('click', function (e) {
    var markAllBtn = e.target.closest('[data-action="mark-all-read"]');
    if (markAllBtn) { markAllReadPage(); return; }

    var markReadBtn = e.target.closest('[data-action="mark-read"]');
    if (markReadBtn) {
        var id = markReadBtn.dataset.notifId;
        if (id) { markReadAjax(id); }
        return;
    }

    var dismissBtn = e.target.closest('[data-action="dismiss"]');
    if (dismissBtn) {
        var id = dismissBtn.dataset.notifId;
        if (id) { dismissNotif(id); }
        return;
    }
});

async function markReadAjax(id) {
    await fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.NOTIF_CSRF }
    });
    const card = document.getElementById('notif-card-' + id);
    if (card) {
        card.classList.add('read');
        const dot = card.querySelector('.notif-unread-dot');
        if (dot) dot.remove();
        const title = card.querySelector('.notif-title');
        if (title) title.classList.add('read');
    }
    if (typeof loadNotificationCount === 'function') loadNotificationCount();
}

async function dismissNotif(id) {
    await fetch('/notifications/' + id + '/dismiss', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.NOTIF_CSRF }
    });
    const card = document.getElementById('notif-card-' + id);
    if (card) { card.style.opacity = '0.3'; }
    setTimeout(() => { if (card) card.remove(); }, 300);
}

async function markAllReadPage() {
    await fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.NOTIF_CSRF }
    });
    location.reload();
}

document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('notifications-list')) return;

    const PRIORITY_CLASS = {
        critical: 'notif-dot-critical',
        high:     'notif-dot-high',
        normal:   'notif-dot-normal',
        low:      'notif-dot-low',
    };

    const paginator = new AjaxPaginator({
        container:         '#notifications-list',
        paginationWrapper: '#notifications-pagination',
        url:               '/notifications',
        perPage:           15,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<div class="notif-empty-state"><div class="notif-empty-desc">No notifications found.</div></div>';
            }
            return items.map(function (n) {
                const dotClass = PRIORITY_CLASS[n.priority] || PRIORITY_CLASS.low;
                const unread   = !n.read_at && !n.is_read;
                return `<div id="notif-card-${n.id}" class="notif-card ${unread ? '' : 'read'}">
                    <div class="notif-card-body">
                        <div class="notif-priority-dot ${dotClass}"></div>
                        <div class="notif-card-content">
                            <div class="notif-title ${unread ? '' : 'read'} mb-xs">${n.title || 'Notification'}</div>
                            <div class="notif-message">${n.message || ''}</div>
                            <div class="notif-time">${n.created_at || ''}</div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        },
    });

    paginator.init();
});
