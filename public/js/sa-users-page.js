document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('sa-users-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const STATUS_BADGE = {
        active:   'background:#f0fdf4;color:#16a34a;',
        pending:  'background:#fffbeb;color:#d97706;',
        inactive: 'background:#fef2f2;color:#dc2626;',
        rejected: 'background:#fef2f2;color:#dc2626;',
    };

    const searchInput = document.getElementById('sa-users-search');
    let searchTimer   = null;

    const paginator = new AjaxPaginator({
        container:         '#sa-users-list',
        paginationWrapper: '#sa-users-pagination',
        url:               '/superadmin/users',
        perPage:           15,
        extraParams: {
            search: searchInput ? searchInput.value.trim() : '',
        },
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="6" style="text-align:center;padding:32px;color:#71717a;">No users found.</td></tr>';
            }
            return items.map(function (user) {
                const isSuperAdmin = Array.isArray(user.roles)
                    ? user.roles.some(function (r) { return r === 'super_admin' || (r && r.name === 'super_admin'); })
                    : false;
                const isActive = !!user.is_active;
                const rolesText = Array.isArray(user.roles)
                    ? user.roles.map(function (r) { return typeof r === 'string' ? r : r.name; }).join(', ')
                    : (user.roles || '—');

                const statusStyle = isActive
                    ? 'background:#f0fdf4;color:#16a34a;border:1px solid rgba(22,163,74,.2);'
                    : 'background:#fef2f2;color:#dc2626;border:1px solid rgba(220,38,38,.2);';

                const impersonateBtn = `<button class="sa-btn sa-btn-impersonate" data-action="impersonate" data-id="${user.id}">👤 Impersonate</button>`;
                const suspendBtn = isActive
                    ? `<button class="sa-btn sa-btn-suspend" data-action="suspend" data-id="${user.id}" data-name="${esc(user.name)}">⏸ Suspend</button>`
                    : `<button class="sa-btn sa-btn-activate" data-action="activate" data-id="${user.id}" data-name="${esc(user.name)}">▶ Activate</button>`;
                const deleteBtn = !isSuperAdmin
                    ? `<button class="sa-btn sa-btn-delete" data-action="delete" data-id="${user.id}" data-name="${esc(user.name)}">🗑 Delete</button>`
                    : '';

                return `<tr>
                    <td style="padding:12px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:#f4f4f5;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:800;color:#71717a;">
                                ${esc(user.avatar || (user.name || '?').slice(0, 2).toUpperCase())}</div>
                            <div>
                                <div style="font-weight:600;color:#0f172a;font-size:0.875rem;">${esc(user.name)}</div>
                                <div style="font-size:0.72rem;color:#94a3b8;">${esc(user.email)}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px 8px;font-size:0.82rem;color:#374151;">${esc(user.organization || '—')}</td>
                    <td style="padding:12px 8px;font-size:0.78rem;color:#64748b;">${esc(rolesText)}</td>
                    <td style="padding:12px 8px;">
                        <span style="font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:20px;${statusStyle}">
                            ${isActive ? 'active' : 'inactive'}</span>
                    </td>
                    <td style="padding:12px 8px;font-size:0.75rem;color:#94a3b8;">${user.created_at ? String(user.created_at).slice(0, 10) : ''}</td>
                    <td style="padding:12px 8px;">
                        <div class="sa-actions-wrap">
                            ${impersonateBtn}
                            ${suspendBtn}
                            ${deleteBtn}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        },
    });

    paginator.init();

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                paginator.extraParams.search = searchInput.value.trim();
                paginator.load(1);
            }, 400);
        });
    }

    // ── Event delegation for action buttons ─────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id     = btn.dataset.id;
        const name   = btn.dataset.name || '';
        const csrf   = document.querySelector('meta[name="csrf-token"]').content;

        if (action === 'impersonate') {
            window.location.href = '/superadmin/users/' + id + '/impersonate';
            return;
        }

        if (action === 'delete') {
            if (!confirm('Permanently delete ' + name + '? This cannot be undone.')) return;
            fetch('/superadmin/users/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showSaToast(data.message, 'success');
                    setTimeout(function () { paginator.load(paginator.currentPage); }, 1000);
                } else {
                    showSaToast(data.message, 'error');
                }
            });
            return;
        }

        if (action === 'suspend' || action === 'activate') {
            fetch('/superadmin/users/' + id + '/' + action, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showSaToast(data.message, 'success');
                    setTimeout(function () { paginator.load(paginator.currentPage); }, 1000);
                } else {
                    showSaToast(data.message, 'error');
                }
            });
        }
    });
});

window.showSaToast = function showSaToast(message, type) {
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
}
