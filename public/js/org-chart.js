document.addEventListener('DOMContentLoaded', function () {

    const dataEl = document.getElementById('org-data');
    if (!dataEl) return;

    const treeData = JSON.parse(dataEl.dataset.tree  || '[]');
    const allUsers = JSON.parse(dataEl.dataset.users || '[]');
    const canEdit  = dataEl.dataset.canEdit === 'true';

    /* ===== VIEW TOGGLE ===== */
    document.querySelectorAll('.org-view-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.org-view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const view = this.dataset.view;
            document.querySelectorAll('.org-view-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('view-' + view)?.classList.remove('hidden');

            if (view === 'list' && !document.getElementById('org-list-body').innerHTML.trim()) {
                renderList();
            }
        });
    });

    /* ===== RENDER TREE ===== */
    function renderTree() {
        const container = document.getElementById('org-tree');
        if (!container) return;

        if (!treeData || treeData.length === 0) {
            container.innerHTML = `
                <div class="org-tree-empty">
                    <div class="org-tree-empty-icon">🌳</div>
                    <h4 class="org-tree-empty-title">No hierarchy set up yet</h4>
                    <p class="org-tree-empty-text">
                        Assign reporting managers to team members to build your org chart.<br>
                        Go to Admin → Users → Edit a user to assign their reporting manager.
                    </p>
                </div>`;
            return;
        }

        let html = '<div class="org-tree-root">';
        treeData.forEach(node => {
            html += buildNodeHtml(node, 0);
        });
        html += '</div>';
        container.innerHTML = html;
    }

    /* ===== BUILD NODE HTML ===== */
    function buildNodeHtml(node, depth) {
        const currentUserId = parseInt(document.body.dataset.userId || '0', 10);
        const isSelf        = node.id === currentUserId;
        const locationIcon  = node.work_location === 'remote' ? '🏠'
            : node.work_location === 'hybrid' ? '🔄' : '🏢';

        let html = `
        <div class="org-node-wrap">
            <div class="org-node${isSelf ? ' org-node-self' : ''}"
                data-id="${node.id}"
                data-name="${escapeHtml(node.name)}">
                <div class="org-node-avatar">
                    ${escapeHtml(node.initials || node.name.charAt(0).toUpperCase())}
                </div>
                <div class="org-node-name">${escapeHtml(node.name)}</div>
                <div class="org-node-title">${escapeHtml(node.job_title || 'Team Member')}</div>
                ${node.department
                    ? `<div class="org-node-dept">${escapeHtml(node.department)}</div>`
                    : ''}
                ${node.reports_count > 0
                    ? `<div class="org-node-reports">${node.reports_count} report${node.reports_count > 1 ? 's' : ''}</div>`
                    : ''}
                <div class="org-node-location">${locationIcon} ${escapeHtml(node.work_location || 'onsite')}</div>
            </div>`;

        if (node.children && node.children.length > 0) {
            html += `<div class="org-children-wrap">
                <div class="org-children-line"></div>
                <div class="org-children-row">`;
            node.children.forEach(child => {
                html += `<div class="org-node-col">`;
                html += buildNodeHtml(child, depth + 1);
                html += `</div>`;
            });
            html += `</div></div>`;
        }

        html += `</div>`;
        return html;
    }

    /* ===== RENDER LIST ===== */
    function renderList() {
        const tbody = document.getElementById('org-list-body');
        if (!tbody) return;

        if (!allUsers.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="au-empty-row">No team members found.</td></tr>`;
            return;
        }

        tbody.innerHTML = allUsers.map(user => {
            const senBadge = user.seniority_level
                ? `<span class="au-seniority-badge au-seniority-${escapeHtml(user.seniority_level)}">${escapeHtml(user.seniority_level)}</span>`
                : '';
            const locBadge = `<span class="location-badge location-${escapeHtml(user.work_location || 'onsite')}">${escapeHtml(user.work_location || 'onsite')}</span>`;
            const editBtn  = canEdit
                ? `<a href="/admin/users/${user.id}/edit" class="sa-btn sa-btn-view">Edit</a>`
                : '—';

            return `<tr>
                <td>
                    <div class="au-user-cell">
                        <div class="au-avatar">${escapeHtml(user.initials)}</div>
                        <div>
                            <div class="au-name">${escapeHtml(user.name)}</div>
                            <div class="au-email">${escapeHtml(user.department || '—')}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="au-name">${escapeHtml(user.job_title || '—')}</div>
                    ${senBadge}
                </td>
                <td>${escapeHtml(user.department || '—')}</td>
                <td>${user.manager ? escapeHtml(user.manager) : '<span class="au-self-note">No manager</span>'}</td>
                <td>${locBadge}</td>
                <td>${editBtn}</td>
            </tr>`;
        }).join('');
    }

    /* ===== SEARCH ===== */
    const searchInput = document.getElementById('org-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            document.querySelectorAll('.org-node').forEach(node => {
                if (!query) {
                    node.classList.remove('search-match', 'search-hidden');
                    return;
                }
                const name = (node.dataset.name || '').toLowerCase();
                if (name.includes(query)) {
                    node.classList.add('search-match');
                    node.classList.remove('search-hidden');
                } else {
                    node.classList.add('search-hidden');
                    node.classList.remove('search-match');
                }
            });
        });
    }

    /* ===== ESCAPE HELPER ===== */
    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    /* ===== INIT ===== */
    renderTree();

});
