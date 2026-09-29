document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('fairness-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const SEV_BADGE = {
        high:   'fv-badge fv-badge-error',
        medium: 'fv-badge fv-badge-warning',
        low:    'fv-badge fv-badge-gray',
    };

    const paginator = new AjaxPaginator({
        container:         '#fairness-list',
        paginationWrapper: '#fairness-pagination',
        url:               '/fairness',
        perPage:           15,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<div class="fv-card" style="padding:40px 24px;text-align:center;">' +
                       '<div style="font-size:2rem;margin-bottom:8px;">✓</div>' +
                       '<div style="font-weight:700;color:#0f172a;">No fairness flags</div></div>';
            }
            return items.map(flag => {
                const sev = flag.severity || 'low';
                const badge = SEV_BADGE[sev] || SEV_BADGE.low;
                const borderColor = sev === 'high' ? '#dc2626' : sev === 'medium' ? '#f59e0b' : '#e4e4e7';
                const pct = Math.round((flag.confidence_score || 0) * 100);
                return `<div style="background:white;border:1px solid #e4e4e7;border-left:3px solid ${borderColor};
                    border-radius:10px;padding:18px 20px;margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                <span class="${badge}" style="text-transform:capitalize;">${esc(sev)}</span>
                                <span style="font-size:0.72rem;color:#64748b;">${esc(String(flag.flag_type || '').replace(/_/g, ' '))}</span>
                            </div>
                            <div style="font-weight:700;font-size:0.95rem;color:#0f172a;">
                                ${esc(flag.suggested_action || flag.flag_type || 'Fairness Issue')}</div>
                            ${flag.flagged_user ? `<div style="font-size:0.78rem;color:#64748b;margin-top:4px;">
                                User: ${esc(typeof flag.flagged_user === 'object' ? flag.flagged_user.name || '' : flag.flagged_user || '')}</div>` : ''}
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:1.4rem;font-weight:800;color:#0f172a;">${pct}%</div>
                            <div style="font-size:0.68rem;color:#94a3b8;">confidence</div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        },
    });

    paginator.init();
});
