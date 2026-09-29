document.addEventListener('DOMContentLoaded', function () {

    /* ===== WEEK BAR CHART ===== */
    const chartEl = document.getElementById('weekChart');
    if (chartEl) {
        const weekData = JSON.parse(chartEl.dataset.weekdays || '[]');
        const maxHours = Math.max(...weekData.map(d => d.hours), 1);

        weekData.forEach(function (day) {
            const heightPct = day.hours > 0
                ? Math.max(8, (day.hours / maxHours) * 100)
                : 8;

            const wrap = document.createElement('div');
            wrap.className = 'perf-week-bar-wrap';

            const bar = document.createElement('div');
            bar.className = 'perf-week-bar'
                + (day.isToday ? ' today' : '')
                + (day.hours === 0 ? ' empty' : '');
            bar.style.height = heightPct + '%';
            bar.title = day.label + ': ' + day.hours + 'h logged';

            const label = document.createElement('div');
            label.className = 'perf-week-label';
            label.textContent = day.label;

            wrap.appendChild(bar);
            wrap.appendChild(label);
            chartEl.appendChild(wrap);
        });
    }

    /* ===== CRITERIA BARS ANIMATION ===== */
    document.querySelectorAll('.perf-criteria-bar').forEach(function (bar) {
        const width = bar.dataset.width || 0;
        setTimeout(function () {
            bar.style.width = width + '%';
        }, 300);
    });

    /* ===== TASK PROGRESS BARS ===== */
    document.querySelectorAll('.perf-task-progress-bar').forEach(function (bar) {
        const width = bar.dataset.width || 0;
        bar.style.setProperty('--progress', width + '%');
    });

    /* ===== GENERIC PCT BARS (perf-bar, team-bar) ===== */
    document.querySelectorAll('.perf-bar[data-pct], .team-bar[data-pct]').forEach(function (bar) {
        const pct = Math.min(100, Math.max(0, parseFloat(bar.dataset.pct) || 0));
        setTimeout(function () { bar.style.width = pct + '%'; }, 300);
    });

    /* ===== BENCHMARK FILL + LINE ===== */
    document.querySelectorAll('.perf-benchmark-fill[data-pct]').forEach(function (el) {
        const pct = Math.min(100, Math.max(0, parseFloat(el.dataset.pct) || 0));
        setTimeout(function () { el.style.width = pct + '%'; }, 300);
    });
    document.querySelectorAll('.perf-benchmark-line[data-pct]').forEach(function (el) {
        const pct = Math.min(100, Math.max(0, parseFloat(el.dataset.pct) || 0));
        setTimeout(function () { el.style.left = pct + '%'; }, 300);
    });

});
