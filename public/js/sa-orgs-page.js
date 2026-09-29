document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('sa-orgs-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const STATUS_STYLE = {
        active:    'background:#f0fdf4;color:#16a34a;border:1px solid rgba(22,163,74,.2);',
        pending:   'background:#fffbeb;color:#d97706;border:1px solid rgba(217,119,6,.2);',
        suspended: 'background:#fef2f2;color:#dc2626;border:1px solid rgba(220,38,38,.2);',
    };
    const PLAN_STYLE = {
        enterprise: 'background:#18181b;color:white;border:1px solid #18181b;',
        pro:        'background:#f4f4f5;color:#3f3f46;border:1px solid #e4e4e7;',
        free:       'background:#fafafa;color:#71717a;border:1px solid #e4e4e7;',
    };

    const searchInput = document.getElementById('sa-orgs-search');
    const urlParams   = new URLSearchParams(window.location.search);
    let searchTimer   = null;

    const orgPaginator = new AjaxPaginator({
        container:         '#sa-orgs-list',
        paginationWrapper: '#sa-orgs-pagination',
        url:               '/superadmin/organizations',
        perPage:           15,
        extraParams: {
            search: searchInput ? searchInput.value.trim() : (urlParams.get('search') || ''),
            status: urlParams.get('status') || '',
        },
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="7" style="text-align:center;padding:32px;color:#71717a;">No organizations found.</td></tr>';
            }
            return items.map(function (org) {
                const isActive = org.is_active || org.status === 'active';
                const sSt = STATUS_STYLE[org.status] || STATUS_STYLE.active;
                const sPl = PLAN_STYLE[org.plan]    || PLAN_STYLE.free;

                const viewBtn = `<a href="/superadmin/organizations/${org.id}" class="sa-btn sa-btn-view">👁 View</a>`;
                const suspendBtn = isActive
                    ? `<button class="sa-btn sa-btn-suspend" data-action="suspend-org" data-id="${org.id}" data-name="${esc(org.name)}">⏸ Suspend</button>`
                    : `<button class="sa-btn sa-btn-activate" data-action="activate-org" data-id="${org.id}" data-name="${esc(org.name)}">▶ Activate</button>`;
                const deleteBtn = `<button class="sa-btn sa-btn-delete" data-action="delete-org" data-id="${org.id}" data-name="${esc(org.name)}">🗑 Delete</button>`;

                return `<tr>
                    <td style="padding:12px 16px;">
                        <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">${esc(org.name)}</div>
                        <div style="font-size:0.72rem;color:#94a3b8;">${esc(org.slug || '')}</div>
                    </td>
                    <td style="padding:12px 8px;">
                        <span style="font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:20px;${sSt}">${esc(org.status)}</span>
                    </td>
                    <td style="padding:12px 8px;">
                        <span style="font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:20px;${sPl}">${esc(org.plan)}</span>
                    </td>
                    <td style="padding:12px 8px;font-size:0.82rem;color:#374151;">${org.members || 0}</td>
                    <td style="padding:12px 8px;">
                        <div style="font-size:0.82rem;font-weight:600;color:#0f172a;">${esc(org.owner_name)}</div>
                        <div style="font-size:0.72rem;color:#94a3b8;">${esc(org.owner_email)}</div>
                    </td>
                    <td style="padding:12px 8px;font-size:0.75rem;color:#94a3b8;">${org.created_at ? String(org.created_at).slice(0, 10) : ''}</td>
                    <td style="padding:12px 8px;">
                        <div class="sa-actions-wrap">
                            ${viewBtn}
                            ${suspendBtn}
                            ${deleteBtn}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        },
    });

    orgPaginator.init();

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                orgPaginator.extraParams.search = searchInput.value.trim();
                orgPaginator.load(1);
            }, 400);
        });
    }

    document.querySelectorAll('[data-status-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const status = btn.dataset.statusFilter;
            orgPaginator.extraParams.status = status === 'all' ? '' : status;
            orgPaginator.load(1);
            document.querySelectorAll('[data-status-filter]').forEach(function (b) {
                b.dataset.active = b === btn ? '1' : '';
            });
        });
    });

    // ── Event delegation for org actions ────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id     = btn.dataset.id;
        const name   = btn.dataset.name || '';
        const csrf   = document.querySelector('meta[name="csrf-token"]').content;

        if (action === 'delete-org') {
            if (!confirm('Delete ' + name + ' and ALL its data permanently?\nThis cannot be undone.')) return;
            fetch('/superadmin/organizations/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showSaToast(data.message, 'success');
                    setTimeout(function () { orgPaginator.load(1); }, 1000);
                } else {
                    showSaToast(data.message, 'error');
                }
            });
        }

        if (action === 'suspend-org' || action === 'activate-org') {
            const endpoint = action === 'suspend-org' ? 'suspend' : 'activate';
            fetch('/superadmin/organizations/' + id + '/' + endpoint, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showSaToast(data.message, 'success');
                    setTimeout(function () { orgPaginator.load(orgPaginator.currentPage); }, 1000);
                } else {
                    showSaToast(data.message, 'error');
                }
            });
        }
    });
});

// showSaToast may already be defined by sa-users-page.js on the same page
if (typeof window.showSaToast === 'undefined') {
    window.showSaToast = function (message, type) {
        var existing = document.querySelector('.sa-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.className = 'sa-toast sa-toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function () { toast.classList.add('visible'); }, 10);
        setTimeout(function () {
            toast.classList.remove('visible');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    };
}
