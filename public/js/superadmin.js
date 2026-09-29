// ── Global SA handlers ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // [data-autosubmit] selects auto-submit their form on change
    document.addEventListener('change', function (e) {
        var el = e.target.closest('[data-autosubmit]');
        if (el && el.form) el.form.submit();
    });

    // [data-confirm] buttons show a confirm dialog before proceeding
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        var msg = btn.dataset.confirm || 'Are you sure?';
        if (!confirm(msg)) e.preventDefault();
    });
});

// ── SA Dashboard charts ───────────────────────────────────────────
function getSAMeta(name) {
    const el = document.querySelector('meta[name="' + name + '"]');
    return el ? JSON.parse(el.getAttribute('content')) : null;
}

function initSACharts() {
    const planEl   = document.getElementById('planChart');
    const growthEl = document.getElementById('growthChart');
    if (!planEl || !growthEl) return;

    const saData   = getSAMeta('sa-chart-data')    || { free: 0, pro: 0, enterprise: 0 };
    const saLabels = getSAMeta('sa-growth-labels') || [];
    const saCounts = getSAMeta('sa-growth-counts') || [];

    new Chart(planEl, {
        type: 'doughnut',
        data: {
            labels: ['Free', 'Pro', 'Enterprise'],
            datasets: [{
                data: [saData.free, saData.pro, saData.enterprise],
                backgroundColor: ['#e4e4e7', '#71717a', '#18181b'],
                borderWidth: 0,
                hoverOffset: 4,
            }],
        },
        options: { responsive: false, plugins: { legend: { display: false } }, cutout: '70%' },
    });

    new Chart(growthEl, {
        type: 'line',
        data: {
            labels: saLabels,
            datasets: [{
                label: 'New Orgs',
                data: saCounts,
                borderColor: '#18181b',
                backgroundColor: 'rgba(24,24,27,0.06)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#18181b',
                pointRadius: 4,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { stepSize: 1, color: '#a1a1aa' } },
                x: { grid: { display: false }, ticks: { color: '#a1a1aa' } },
            },
        },
    });
}
