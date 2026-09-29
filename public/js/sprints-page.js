document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('sprints-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    const STATUS_COLOR = {
        active:    { border: '#16a34a', bg: '#dcfce7', text: '#16a34a' },
        planning:  { border: '#3b82f6', bg: '#dbeafe', text: '#1d4ed8' },
        completed: { border: '#a1a1aa', bg: '#f4f4f5', text: '#71717a' },
    };

    const paginator = new AjaxPaginator({
        container:         '#sprints-list',
        paginationWrapper: '#sprints-pagination',
        url:               window.location.pathname,
        perPage:           10,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<div style="text-align:center;padding:40px 24px;color:#71717a;background:white;' +
                       'border:1px solid #e4e4e7;border-radius:12px;">No sprints found.</div>';
            }
            const groups = { active: [], planning: [], completed: [] };
            items.forEach(s => { (groups[s.status] || groups.active).push(s); });
            return Object.entries(groups).filter(([, arr]) => arr.length).map(([status, sprints]) => {
                const c = STATUS_COLOR[status] || STATUS_COLOR.completed;
                const cards = sprints.map(s => {
                    const proj = s.project ? esc(s.project.name || s.project) : '';
                    const pct  = s.completion_pct || 0;
                    return `<div style="background:white;border:1px solid #e4e4e7;
                        border-left:4px solid ${c.border};border-radius:12px;
                        padding:18px 20px;display:flex;flex-direction:column;gap:8px;margin-bottom:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <div>
                                <div style="font-weight:800;font-size:0.95rem;color:#09090b;">${esc(s.name)}</div>
                                <div style="font-size:0.75rem;color:#71717a;margin-top:2px;">${proj}</div>
                            </div>
                            <span style="background:${c.bg};color:${c.text};padding:3px 10px;border-radius:99px;
                                font-size:0.72rem;font-weight:700;">${esc(s.status)}</span>
                        </div>
                        <div style="display:flex;gap:12px;font-size:0.75rem;color:#64748b;flex-wrap:wrap;">
                            <span>${s.total_tasks || 0} tasks</span>
                            <span>${s.completed_tasks || 0} done</span>
                            <span>${pct}% complete</span>
                        </div>
                    </div>`;
                }).join('');
                const label = status === 'active' ? '🟢 Active Sprints'
                    : status === 'planning' ? '📋 Planning' : '✅ Completed';
                return `<div style="font-size:0.72rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;
                    letter-spacing:0.06em;margin-bottom:8px;margin-top:16px;">${label}</div>
                    ${cards}`;
            }).join('');
        },
    });

    paginator.init();
});
