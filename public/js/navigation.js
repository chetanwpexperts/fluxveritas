// ─── Sidebar ─────────────────────────────────────────────────────────────────
let fvCollapsed = localStorage.getItem('fv_sidebar') === '1';

function applySidebar(animate) {
    const sb  = document.getElementById('fv-sidebar');
    const mn  = document.getElementById('fv-main');
    const ft  = document.getElementById('fv-footer');
    const isMobile = window.innerWidth < 768;
    if (!animate) sb.style.transition = 'none';
    if (isMobile) {
        sb.style.width = '220px';
        if (mn) mn.style.marginLeft = '0';
        if (ft) ft.style.marginLeft = '0';
    } else {
        const w = fvCollapsed ? '64px' : '220px';
        sb.style.width = w;
        if (mn) mn.style.marginLeft = w;
        if (ft) ft.style.marginLeft = w;
    }
    if (!animate) requestAnimationFrame(() => { sb.style.transition = 'width 0.3s ease'; });
}

function toggleSidebar() {
    if (window.innerWidth < 768) {
        toggleMobileSidebar();
        return;
    }
    fvCollapsed = !fvCollapsed;
    localStorage.setItem('fv_sidebar', fvCollapsed ? '1' : '0');
    applySidebar(true);
}

function toggleMobileSidebar() {
    const sb = document.getElementById('fv-sidebar');
    const ov = document.getElementById('fv-overlay');
    const open = sb.getAttribute('data-mobile') === '1';
    if (open) {
        sb.style.left = '-220px';
        sb.setAttribute('data-mobile','0');
        ov.style.display = 'none';
    } else {
        sb.style.left = '0';
        sb.setAttribute('data-mobile','1');
        ov.style.display = 'block';
    }
}

function closeMobileSidebar() {
    const sb = document.getElementById('fv-sidebar');
    sb.style.left = '-220px';
    sb.setAttribute('data-mobile','0');
    document.getElementById('fv-overlay').style.display = 'none';
}

// ─── Notifications ────────────────────────────────────────────────────────────
let notifOpen = false;

function toggleNotifications() {
    notifOpen = !notifOpen;
    document.getElementById('notif-dropdown').style.display = notifOpen ? 'block' : 'none';
    if (notifOpen) loadNotifications();
}

async function loadNotifications() {
    const list = document.getElementById('notif-list');
    try {
        const res  = await fetch('/notifications/latest');
        const data = await res.json();
        if (data.length === 0) {
            list.innerHTML = '<div style="padding:32px;text-align:center;color:#71717a;font-size:0.82rem;">No notifications yet</div>';
            return;
        }
        list.innerHTML = data.map(n => `
            <div onclick="markNotifRead(${n.id})" style="padding:12px 16px;border-bottom:1px solid #f4f4f5;cursor:pointer;background:${n.is_read ? 'white' : '#fafafa'};"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='${n.is_read ? 'white' : '#fafafa'}'">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:6px;background:${n.priority==='critical'?'#dc2626':n.priority==='high'?'#f59e0b':n.is_read?'#e4e4e7':'#3b82f6'};"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.8rem;font-weight:600;color:#09090b;margin-bottom:3px;">${n.title}</div>
                        <div style="font-size:0.72rem;color:#71717a;line-height:1.4;">${n.message.substring(0,100)}${n.message.length>100?'...':''}</div>
                        ${n.action_url ? `<a href="${n.action_url}" style="font-size:0.72rem;color:#18181b;font-weight:600;text-decoration:none;margin-top:4px;display:inline-block;">${n.action_label} →</a>` : ''}
                        <div style="font-size:0.68rem;color:#a1a1aa;margin-top:4px;">${n.time_ago}</div>
                    </div>
                </div>
            </div>`).join('');
    } catch(e) {
        list.innerHTML = '<div style="padding:32px;text-align:center;color:#71717a;font-size:0.82rem;">Failed to load</div>';
    }
}

async function markNotifRead(id) {
    await fetch('/notifications/' + id + '/read', { method: 'POST', headers: { 'X-CSRF-TOKEN': window.NAV_CSRF } });
    loadNotificationCount();
    loadNotifications();
}

async function markAllRead() {
    await fetch('/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': window.NAV_CSRF } });
    loadNotifications();
    loadNotificationCount();
}

async function loadNotificationCount() {
    try {
        const res = await fetch('/notifications/count', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        if (!res.ok) return;
        const data  = await res.json();
        const badge = document.getElementById('notif-badge');
        if (data.count > 0) {
            badge.style.display = 'flex';
            badge.textContent   = data.count > 9 ? '9+' : data.count;
        } else {
            badge.style.display = 'none';
        }
    } catch(e) {}
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#notif-wrapper')) {
        const d = document.getElementById('notif-dropdown');
        if (d) { d.style.display = 'none'; notifOpen = false; }
    }
});

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth < 768) {
        const sb = document.getElementById('fv-sidebar');
        sb.style.left = '-220px';
        sb.style.transition = 'left 0.3s ease, width 0.3s ease';
        const mn = document.getElementById('fv-main');
        const ft = document.getElementById('fv-footer');
        if (mn) mn.style.marginLeft = '0';
        if (ft) ft.style.marginLeft = '0';
    } else {
        applySidebar(false);
    }
    if (document.body.getAttribute('data-auth') === '1') {
        loadNotificationCount();
        setInterval(loadNotificationCount, 60000);
    }
});
