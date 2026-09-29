document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('increment-reviews-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const STATUS_STYLE = {
        approved:  'background:#f0fdf4;color:#16a34a;',
        pending:   'background:#fffbeb;color:#d97706;',
        rejected:  'background:#fef2f2;color:#dc2626;',
        draft:     'background:#f4f4f5;color:#71717a;',
    };

    const paginator = new AjaxPaginator({
        container:         '#increment-reviews-list',
        paginationWrapper: '#increment-reviews-pagination',
        url:               window.location.pathname,
        perPage:           15,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="7" style="text-align:center;padding:32px;color:#71717a;">No reviews found.</td></tr>';
            }
            return items.map(r => {
                const st = STATUS_STYLE[r.status] || STATUS_STYLE.draft;
                const name   = r.user ? esc(r.user.name) : esc(r.name || '—');
                const email  = r.user ? esc(r.user.email) : '';
                const avatar = name.charAt(0) || '?';
                return `<tr style="border-bottom:1px solid #f4f4f5;">
                    <td style="padding:12px 16px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:#f4f4f5;
                                display:flex;align-items:center;justify-content:center;
                                font-size:0.7rem;font-weight:800;color:#71717a;">${avatar}</div>
                            <div>
                                <div style="font-weight:600;color:#0f172a;font-size:0.875rem;">${name}</div>
                                <div style="font-size:0.72rem;color:#94a3b8;">${email}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px 8px;font-size:0.875rem;font-weight:600;color:#0f172a;">
                        ${r.avg_score !== undefined ? r.avg_score : '—'}</td>
                    <td style="padding:12px 8px;font-size:0.875rem;color:#374151;">
                        ${r.recommended_increment !== undefined ? r.recommended_increment + '%' : '—'}</td>
                    <td style="padding:12px 8px;font-size:0.875rem;color:#374151;">
                        ${r.final_increment !== undefined ? r.final_increment + '%' : '—'}</td>
                    <td style="padding:12px 8px;">
                        <span style="font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:20px;${st}">
                            ${esc(r.status || '—')}</span>
                    </td>
                    <td style="padding:12px 8px;font-size:0.78rem;color:#94a3b8;">${r.review_year || ''}</td>
                    <td style="padding:12px 8px;"></td>
                </tr>`;
            }).join('');
        },
    });

    paginator.init();
});
