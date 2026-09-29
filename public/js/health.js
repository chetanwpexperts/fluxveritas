document.addEventListener('DOMContentLoaded', function () {
    // .agent-run-form — intercept submit and call runAgentNow
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('.agent-run-form');
        if (!form) return;
        runAgentNow(e, form);
    });

    // [data-action="agent-run-full"] — run agent without a real form
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action="agent-run-full"]');
        if (!btn) return;
        runAgentFull();
    });
});

async function runAgentNow(event, form) {
    event.preventDefault();
    const btn = document.getElementById('run-btn') || (form && form.querySelector('button[type=submit]'));
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Running...'; }

    try {
        const res  = await fetch(window.AGENT_RUN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.HEALTH_CSRF },
        });
        const data = await res.json();

        const box   = document.getElementById('run-result');
        const lines = document.getElementById('run-lines');
        box.style.display = 'block';
        lines.innerHTML = '';

        const overall = data.overall_status === 'healthy' ? '✅' : (data.overall_status === 'warning' ? '⚠️' : '🚨');
        addLine(`${overall} Overall status: <strong>${data.overall_status}</strong>`);
        addLine(`📋 Team actions taken: <strong>${data.actions}</strong>`);
        addLine(`🔐 Security issues: <strong>${data.security_issues}</strong>`);

        if (data.results && data.results.length > 0) {
            addLine('');
            addLine('<strong>Actions:</strong>');
            data.results.slice(0, 10).forEach(function (r) {
                addLine('  [' + r.type + '] ' + r.message);
            });
        }

        if (data.health) {
            addLine('');
            addLine('<strong>Health:</strong>');
            Object.entries(data.health).forEach(function (entry) {
                var name  = entry[0];
                var check = entry[1];
                var icon  = check.status === 'healthy' ? '✅' : (check.status === 'warning' ? '⚠️' : '🚨');
                addLine('  ' + icon + ' ' + name + ': ' + (check.message || check.status));
            });
        }

        if (btn) { btn.disabled = false; btn.textContent = '🔄 Run Check Now'; }
        setTimeout(function () { location.reload(); }, 3000);
    } catch (e) {
        if (btn) { btn.disabled = false; btn.textContent = '🔄 Run Check Now'; }
        alert('Agent run failed: ' + e.message);
    }
}

function addLine(html) {
    var d = document.createElement('div');
    d.className = 'run-line';
    d.innerHTML = html;
    document.getElementById('run-lines').appendChild(d);
}

function runAgentFull() {
    runAgentNow({ preventDefault: function () {} }, null);
}
