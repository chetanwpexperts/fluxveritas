let criteriaCount = window.CRITERIA_COUNT || 0;
const sources = ['github_commits','github_prs','work_logs','tasks_completed','task_complexity','blocker_resolution','attendance'];

function addCriteria() {
    const i = criteriaCount++;
    const row = document.createElement('div');
    row.className = 'criteria-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 2fr 100px 36px;gap:10px;align-items:center;';
    row.innerHTML = `
        <input type="text" name="criteria[${i}][label]" placeholder="e.g. GitHub Commits"
            style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;" oninput="syncName(this,${i})">
        <select name="criteria[${i}][type]" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
            <option value="automatic">Automatic</option>
            <option value="manual">Manual</option>
        </select>
        <select name="criteria[${i}][source]" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;">
            <option value="">— Source —</option>
            ${sources.map(s=>`<option value="${s}">${s.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</option>`).join('')}
        </select>
        <input type="hidden" name="criteria[${i}][name]" id="name_${i}" value="">
        <div style="position:relative;">
            <input type="number" name="criteria[${i}][weight]" placeholder="0" step="0.5" min="0"
                class="weight-input" onchange="updateTotal()"
                style="width:100%;padding:8px 22px 8px 8px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
            <span style="position:absolute;right:7px;top:50%;transform:translateY(-50%);color:#71717a;font-size:12px;">%</span>
        </div>
        <button type="button" onclick="this.closest('.criteria-row').remove();updateTotal()"
            style="width:36px;height:36px;border:1px solid #fecaca;border-radius:8px;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:16px;">×</button>
    `;
    document.getElementById('criteriaList').appendChild(row);
}

function syncName(input, i) {
    const nameField = document.getElementById('name_' + i);
    if (nameField) nameField.value = input.value.toLowerCase().replace(/\s+/g,'_');
}

function updateTotal() {
    const total = Array.from(document.querySelectorAll('.weight-input')).reduce((sum,el)=>sum+(parseFloat(el.value)||0),0);
    const max = parseFloat(document.getElementById('maxIncrement').value)||0;
    document.getElementById('totalWeight').textContent = total.toFixed(1);
    document.getElementById('maxLabel').textContent = max.toFixed(0);
    const counter = document.getElementById('weightCounter');
    counter.style.background = Math.abs(total - max) < 0.1 ? '#f0fdf4' : '#fef2f2';
    counter.style.color = Math.abs(total - max) < 0.1 ? '#166534' : '#991b1b';
}

document.getElementById('maxIncrement').addEventListener('input', updateTotal);

const presets = {
    engineering: [
        {label:'GitHub Commits',type:'automatic',source:'github_commits',weight:8},
        {label:'Pull Requests',type:'automatic',source:'github_prs',weight:5},
        {label:'Task Complexity',type:'automatic',source:'task_complexity',weight:7},
        {label:'Work Log Consistency',type:'automatic',source:'work_logs',weight:5},
        {label:'Blocker Resolution',type:'automatic',source:'blocker_resolution',weight:3},
        {label:'Attendance',type:'automatic',source:'attendance',weight:2},
    ],
    sales: [
        {label:'Work Log Consistency',type:'automatic',source:'work_logs',weight:10},
        {label:'Tasks Completed',type:'automatic',source:'tasks_completed',weight:10},
        {label:'Attendance',type:'automatic',source:'attendance',weight:5},
        {label:'Blocker Resolution',type:'automatic',source:'blocker_resolution',weight:5},
    ],
    hr: [
        {label:'Work Log Consistency',type:'automatic',source:'work_logs',weight:12},
        {label:'Attendance',type:'automatic',source:'attendance',weight:8},
        {label:'Tasks Completed',type:'automatic',source:'tasks_completed',weight:10},
    ],
    finance: [
        {label:'Work Log Consistency',type:'automatic',source:'work_logs',weight:12},
        {label:'Tasks Completed',type:'automatic',source:'tasks_completed',weight:10},
        {label:'Attendance',type:'automatic',source:'attendance',weight:8},
    ],
    operations: [
        {label:'Work Log Consistency',type:'automatic',source:'work_logs',weight:10},
        {label:'Tasks Completed',type:'automatic',source:'tasks_completed',weight:8},
        {label:'Blocker Resolution',type:'automatic',source:'blocker_resolution',weight:7},
        {label:'Attendance',type:'automatic',source:'attendance',weight:5},
    ],
};

function loadPreset(type) {
    document.getElementById('criteriaList').innerHTML = '';
    criteriaCount = 0;
    (presets[type]||[]).forEach(p => {
        addCriteria();
        const rows = document.querySelectorAll('.criteria-row');
        const row = rows[rows.length-1];
        row.querySelector('[name$="[label]"]').value = p.label;
        row.querySelector('[name$="[type]"]').value = p.type;
        row.querySelector('[name$="[source]"]').value = p.source;
        row.querySelector('[name$="[weight]"]').value = p.weight;
        const i = criteriaCount - 1;
        const nameField = document.getElementById('name_'+i);
        if (nameField) nameField.value = p.source;
        updateTotal();
    });
    updateTotal();
}

updateTotal();
