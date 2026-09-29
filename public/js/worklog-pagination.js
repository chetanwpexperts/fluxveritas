document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('worklog-history-list')) return;

    const CATEGORIES = {
        meeting: 'Meeting', code_review: 'Code Review', development: 'Development',
        research: 'Research', support: 'Support', training: 'Training',
        travel: 'Travel', client_call: 'Client Call', vendor_call: 'Vendor Call',
        planning: 'Planning', documentation: 'Documentation', design: 'Design',
        testing: 'Testing', reporting: 'Reporting', recruitment: 'Recruitment',
        other: 'Other',
    };

    function catClass(cat) { return (cat || 'other').replace(/_/g, '-'); }
    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const paginator = new AjaxPaginator({
        container:         '#worklog-history-list',
        paginationWrapper: '#worklog-history-pagination',
        url:               '/work-log',
        perPage:           15,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<div class="wl-empty-state"><div class="wl-empty-icon">✏️</div>' +
                       '<div class="wl-empty-text">No logs yet.</div></div>';
            }
            const byDate = {};
            items.forEach(log => {
                const d = (log.log_date || '').slice(0, 10);
                if (!byDate[d]) byDate[d] = [];
                byDate[d].push(log);
            });
            return Object.entries(byDate).map(([date, logs]) => {
                const dayMins = logs.reduce((s, l) => s + (l.duration_minutes || 0), 0);
                const label   = date === window.WL_TODAY ? 'Today' :
                    new Date(date + 'T00:00:00').toLocaleDateString('en-US',
                        { weekday: 'long', month: 'short', day: 'numeric' });
                const rows = logs.map(log => {
                    const cc = catClass(log.category);
                    return `<div class="wl-log-entry">
                        <div class="wl-log-strip cat-strip-${cc}"></div>
                        <div class="wl-log-body">
                            <div class="wl-log-top">
                                <div class="wl-log-info">
                                    <div class="wl-log-title">${esc(log.title)}</div>
                                    <div class="wl-log-meta">
                                        <span class="wl-cat-badge cat-badge-${cc}">
                                            ${CATEGORIES[log.category] || log.category}</span>
                                        ${log.duration_minutes ? `<span class="wl-log-duration">
                                            ${Math.floor(log.duration_minutes/60)}h
                                            ${log.duration_minutes%60}m</span>` : ''}
                                        ${log.output_value ? `<span class="wl-log-output">
                                            ★ ${log.output_value}/10</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `<div class="wl-day-group">
                    <div class="wl-day-header${date === window.WL_TODAY ? ' wl-day-header--today' : ''}">
                        <div class="wl-day-label">${label}</div>
                        <div class="wl-day-rule"></div>
                        ${dayMins ? `<div class="wl-day-hours">${(dayMins/60).toFixed(1)}h</div>` : ''}
                    </div>
                    <div class="fv-card wl-day-card">${rows}</div>
                </div>`;
            }).join('');
        },
    });

    paginator.init();
});
