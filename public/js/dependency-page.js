document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('dependency-list')) return;

    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    var PRIORITY_STYLE = {
        critical: 'background:#fef2f2;color:#dc2626;',
        high:     'background:#fff7ed;color:#ea580c;',
        medium:   'background:#fffbeb;color:#d97706;',
        low:      'background:#f4f4f5;color:#71717a;',
    };

    var paginator = new AjaxPaginator({
        container:         '#dependency-list',
        paginationWrapper: '#dependency-pagination',
        url:               window.location.pathname,
        perPage:           15,
        extraParams:       { type: 'open' },
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="7" style="text-align:center;padding:32px;color:#71717a;">No open blockers found.</td></tr>';
            }
            return items.map(function (b) {
                var pStyle      = PRIORITY_STYLE[b.priority] || PRIORITY_STYLE.low;
                var blockedName = b.blocked_user  ? esc(b.blocked_user.name)  : '—';
                var blockingName= b.blocking_user ? esc(b.blocking_user.name) : '—';
                var projName    = b.project       ? esc(b.project.name)       : '—';
                return '<tr>' +
                    '<td class="fv-td">' +
                        '<div style="font-weight:600;color:#0f172a;font-size:0.875rem;">' + esc(b.title) + '</div>' +
                        '<div style="font-size:0.75rem;color:#94a3b8;">' + esc(String(b.blocker_type || '').replace(/_/g, ' ')) + '</div>' +
                    '</td>' +
                    '<td class="fv-td">' +
                        '<span style="font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:20px;' + pStyle + '">' + esc(b.priority || '—') + '</span>' +
                    '</td>' +
                    '<td class="fv-td" style="font-size:0.82rem;">' + blockedName + '</td>' +
                    '<td class="fv-td" style="font-size:0.82rem;">' + blockingName + '</td>' +
                    '<td class="fv-td" style="font-size:0.82rem;">' + projName + '</td>' +
                    '<td class="fv-td" style="font-size:0.75rem;color:#94a3b8;">' + (b.days_open || 0) + ' days</td>' +
                    '<td class="fv-td" style="font-size:0.75rem;color:#94a3b8;">' + esc(b.created_at || '') + '</td>' +
                '</tr>';
            }).join('');
        },
    });

    paginator.init();

    var searchInput = document.getElementById('dependency-search');
    var searchTimer = null;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                paginator.extraParams.search = searchInput.value;
                paginator.load(1);
            }, 400);
        });
    }
});
