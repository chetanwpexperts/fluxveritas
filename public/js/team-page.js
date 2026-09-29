document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('team-members-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const paginator = new AjaxPaginator({
        container:         '#team-members-list',
        paginationWrapper: '#team-members-pagination',
        url:               window.location.pathname,
        perPage:           20,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="6" style="text-align:center;padding:32px;color:#71717a;">No team members.</td></tr>';
            }
            return items.map(member => {
                const name  = esc(member.name);
                const email = esc(member.email);
                const av    = (member.name || '?').slice(0, 2).toUpperCase();
                const roles = Array.isArray(member.roles) ? member.roles : [];
                const roleBadges = roles.length
                    ? roles.map(r => {
                        const rn = typeof r === 'object' ? (r.name || '') : r;
                        return `<span class="fv-badge fv-badge-gray">${esc(rn)}</span>`;
                    }).join('')
                    : `<span class="fv-badge fv-badge-gray">${esc(member.role || 'employee')}</span>`;
                return `<tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:#f4f4f5;
                                border:1px solid #e4e4e7;display:flex;align-items:center;
                                justify-content:center;font-size:0.72rem;font-weight:800;color:#71717a;">${av}</div>
                            <div>
                                <div style="font-weight:600;color:#0f172a;font-size:0.875rem;">${name}</div>
                                <div style="font-size:0.75rem;color:#94a3b8;">${email}</div>
                            </div>
                        </div>
                    </td>
                    <td><div style="display:flex;flex-wrap:wrap;gap:4px;">${roleBadges}</div></td>
                    <td style="font-size:0.82rem;color:#64748b;">${member.stat_commits || 0}</td>
                    <td style="font-size:0.82rem;color:#64748b;">${member.stat_prs || 0}</td>
                    <td style="font-size:0.75rem;color:#94a3b8;">${member.last_active ? String(member.last_active).slice(0, 10) : 'Never'}</td>
                    <td></td>
                </tr>`;
            }).join('');
        },
    });

    paginator.init();
});
