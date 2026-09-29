document.addEventListener('DOMContentLoaded', function () {
    if (!window.AjaxPaginator) return;
    if (!document.getElementById('admin-users-list')) return;

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    function roleBadges(roles) {
        if (!Array.isArray(roles) || !roles.length) {
            return '<span class="fv-badge fv-badge-gray au-role-badge">No role</span>';
        }
        return roles.map(r => {
            const name = typeof r === 'object' ? r.name : r;
            return `<span class="fv-badge fv-badge-gray au-role-badge">${esc(name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()))}</span>`;
        }).join('');
    }

    function seniorityBadge(level) {
        if (!level) return '';
        return `<span class="au-seniority-badge au-seniority-${esc(level)}">${esc(level.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()))}</span>`;
    }

    function employmentBadge(type) {
        if (!type) return '';
        return `<span class="au-employment-badge au-emp-${esc(type)}">${esc(type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()))}</span>`;
    }

    const paginator = new AjaxPaginator({
        container:         '#admin-users-list',
        paginationWrapper: '#admin-users-pagination',
        url:               window.location.pathname,
        perPage:           15,
        onRender: function (items) {
            if (!items || !items.length) {
                return '<tr><td colspan="7" class="au-empty-row">No users found.</td></tr>';
            }
            return items.map(user => {
                const roles = Array.isArray(user.roles) ? user.roles : [];
                const designation = user.designation
                    ? `<span class="au-designation-tag">${esc(user.designation)}</span>` : '';
                const manager = user.reporting_manager
                    ? esc(typeof user.reporting_manager === 'object' ? user.reporting_manager.name : user.reporting_manager)
                    : '—';
                const jobTitle = user.job_title
                    ? `<div class="au-job-title">${esc(user.job_title)}</div>` : '';
                const statusBadge = user.is_active
                    ? '<span class="fv-badge fv-badge-green">Active</span>'
                    : '<span class="fv-badge fv-badge-red">Inactive</span>';
                const editBtn = `<a href="/admin/users/${user.id}/edit" class="sa-btn sa-btn-view">Edit</a>`;
                const permBtn = `<a href="/admin/permissions?user=${user.id}" class="sa-btn sa-btn-view">Permissions</a>`;

                return `<tr>
                    <td>
                        <div class="au-user-cell">
                            <div class="au-avatar">${esc((user.name || '?').slice(0, 2).toUpperCase())}</div>
                            <div>
                                <div class="au-name">${esc(user.name)}</div>
                                <div class="au-email">${esc(user.email)}</div>
                                ${jobTitle}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="au-role-cell">
                            ${roleBadges(roles)}
                            ${designation}
                        </div>
                    </td>
                    <td>
                        <div class="au-level-cell">
                            ${seniorityBadge(user.seniority_level)}
                            ${employmentBadge(user.employment_type)}
                        </div>
                    </td>
                    <td class="au-manager-cell">${manager}</td>
                    <td>${statusBadge}</td>
                    <td class="au-date-cell">${user.created_at ? user.created_at.slice(0, 10) : ''}</td>
                    <td class="au-actions-col">
                        <div class="au-actions">
                            ${editBtn}
                            ${permBtn}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        },
    });

    paginator.init();
});
