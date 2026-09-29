document.addEventListener('DOMContentLoaded', function () {

    /* ─────────────────────────────────────────────────────────────────
       TASK LINKING — load user's active tasks into selector
    ───────────────────────────────────────────────────────────────── */

    function loadUserTasks() {
        const select = document.getElementById('quicklog-task-select');
        if (!select) return;
        fetch('/work-log/user-tasks', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(tasks => {
            tasks.forEach(task => {
                const option = document.createElement('option');
                option.value = task.id;
                option.textContent = (task.ticket_number ? task.ticket_number + ' — ' : '') + task.title;
                select.appendChild(option);
            });
            // Pre-select if URL has ?task=ID
            const urlParams = new URLSearchParams(window.location.search);
            const taskId = urlParams.get('task');
            if (taskId) select.value = taskId;
        })
        .catch(() => {});
    }

    loadUserTasks();

    /* ─────────────────────────────────────────────────────────────────
       HELPERS
    ───────────────────────────────────────────────────────────────── */

    const CSRF  = () => document.querySelector('meta[name="csrf-token"]').content;
    const ROUTES = window.WL_ROUTES || {};
    const TODAY  = window.WL_TODAY  || new Date().toISOString().slice(0, 10);

    // Load designation-aware categories from server
    const _rawCategories = JSON.parse(
        document.getElementById('worklog-data')?.dataset?.categories || '[]'
    );
    const CATEGORIES = _rawCategories.length > 0
        ? Object.fromEntries(_rawCategories.map(c => [
            c.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, ''),
            c
          ]))
        : {
            meeting: 'Meeting', code_review: 'Code Review', development: 'Development',
            research: 'Research', support: 'Support', training: 'Training',
            travel: 'Travel', client_call: 'Client Call', vendor_call: 'Vendor Call',
            planning: 'Planning', documentation: 'Documentation', design: 'Design',
            testing: 'Testing', reporting: 'Reporting', recruitment: 'Recruitment',
            other: 'Other',
          };

    const CAT_CLASSES = {
        meeting: '#3b82f6', code_review: '#8b5cf6', development: '#10b981',
        research: '#f59e0b', support: '#ef4444', training: '#06b6d4',
        travel: '#f97316', client_call: '#84cc16', vendor_call: '#ec4899',
        planning: '#6366f1', documentation: '#14b8a6', design: '#a855f7',
        testing: '#0ea5e9', reporting: '#64748b', recruitment: '#d946ef',
        other: '#71717a',
    };

    function catClass(cat) { return (cat || 'other').replace(/_/g, '-'); }

    function formatDate(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }

    function formatDateLong(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function apiFetch(url, method, body) {
        const opts = {
            method: method || 'GET',
            headers: {
                'X-CSRF-TOKEN': CSRF(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts).then(r => r.json());
    }

    /* ─────────────────────────────────────────────────────────────────
       TOAST NOTIFICATIONS
    ───────────────────────────────────────────────────────────────── */

    let toastContainer = document.querySelector('.wl-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'wl-toast-container';
        document.body.appendChild(toastContainer);
    }

    function showToast(message, type) {
        type = type || 'success';
        const icons = { success: '✓', error: '✕', info: 'ℹ' };
        const toast = document.createElement('div');
        toast.className = 'wl-toast wl-toast--' + type;
        toast.innerHTML = '<span class="wl-toast-icon">' + icons[type] + '</span><span>' + message + '</span>';
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    /* ─────────────────────────────────────────────────────────────────
       TAB SWITCHING
    ───────────────────────────────────────────────────────────────── */

    const tabBtns   = document.querySelectorAll('.wl-tab-btn');
    const tabPanels = document.querySelectorAll('.wl-tab-panel');

    function switchTab(targetId) {
        tabBtns.forEach(b => b.classList.toggle('wl-tab-btn--active', b.dataset.tab === targetId));
        tabPanels.forEach(p => {
            const active = p.id === 'tab-' + targetId;
            p.classList.toggle('wl-tab-panel--active', active);
            p.classList.toggle('wl-tab-panel--hidden', !active);
        });
        if (targetId === 'weekly')   initWeeklyGrid();
        if (targetId === 'templates') loadTemplates();
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // Activate first tab on load
    switchTab('quicklog');

    /* ─────────────────────────────────────────────────────────────────
       TAB 1: QUICK LOG
    ───────────────────────────────────────────────────────────────── */

    const quickText   = document.getElementById('wl-quick-text');
    const quickDate   = document.getElementById('wl-quick-date');
    const quickSubmit = document.getElementById('wl-quick-submit');
    const aiResult    = document.getElementById('wl-ai-result');
    const aiTitle     = document.getElementById('wl-ai-title');
    const aiCat       = document.getElementById('wl-ai-cat');
    const hourPills   = document.getElementById('wl-hour-pills');
    const outputPills = document.getElementById('wl-output-pills');
    const customInput = document.getElementById('wl-custom-minutes');
    const copyYest    = document.getElementById('wl-copy-yesterday');

    let selectedMinutes = null;
    let selectedOutput  = 5;

    // Set default output pill active
    if (outputPills) {
        const defPill = outputPills.querySelector('[data-output="5"]');
        if (defPill) defPill.classList.add('wl-pill--active');
    }

    // Hour pill selection
    if (hourPills) {
        hourPills.addEventListener('click', function (e) {
            const pill = e.target.closest('.wl-pill');
            if (!pill) return;
            hourPills.querySelectorAll('.wl-pill').forEach(p => p.classList.remove('wl-pill--active'));
            pill.classList.add('wl-pill--active');

            if (pill.dataset.minutes === 'custom') {
                customInput.classList.remove('wl-hidden');
                customInput.focus();
                selectedMinutes = null;
            } else {
                customInput.classList.add('wl-hidden');
                selectedMinutes = parseInt(pill.dataset.minutes, 10);
            }
        });
    }

    if (customInput) {
        customInput.addEventListener('input', function () {
            selectedMinutes = parseInt(this.value, 10) || null;
        });
    }

    // Output pill selection
    if (outputPills) {
        outputPills.addEventListener('click', function (e) {
            const pill = e.target.closest('.wl-pill--output');
            if (!pill) return;
            outputPills.querySelectorAll('.wl-pill--output').forEach(p => p.classList.remove('wl-pill--active'));
            pill.classList.add('wl-pill--active');
            selectedOutput = parseInt(pill.dataset.output, 10);
        });
    }

    // Quick log submit
    if (quickSubmit) {
        quickSubmit.addEventListener('click', function () {
            const text = quickText ? quickText.value.trim() : '';
            const date = quickDate ? quickDate.value : TODAY;
            const mins = selectedMinutes || (customInput ? parseInt(customInput.value, 10) : null);

            if (!text) { showToast('Please describe what you worked on.', 'error'); return; }
            if (!mins || mins < 15) { showToast('Please select a duration (min 15 mins).', 'error'); return; }

            quickSubmit.disabled = true;
            quickSubmit.textContent = 'Logging…';

            // Show loading
            let loadingEl = document.getElementById('wl-ai-loading');
            if (!loadingEl) {
                loadingEl = document.createElement('div');
                loadingEl.id = 'wl-ai-loading';
                loadingEl.className = 'wl-ai-loading';
                loadingEl.innerHTML = '<div class="wl-spinner"></div><span>AI is parsing your log…</span>';
                aiResult.parentNode.insertBefore(loadingEl, aiResult);
            }
            loadingEl.classList.add('wl-ai-loading--visible');
            if (aiResult) aiResult.classList.remove('wl-ai-result--visible');

            apiFetch(ROUTES.quick, 'POST', {
                text: text,
                log_date: date,
                duration_minutes: mins,
                output_value: selectedOutput,
                task_id: document.getElementById('quicklog-task-select')?.value || null,
            }).then(data => {
                loadingEl.classList.remove('wl-ai-loading--visible');
                quickSubmit.disabled = false;
                quickSubmit.textContent = 'Log with AI →';

                if (data.success) {
                    aiTitle.textContent = data.parsed.title;
                    aiCat.textContent   = '📂 ' + (CATEGORIES[data.parsed.category] || data.parsed.category);
                    aiResult.classList.add('wl-ai-result--visible');
                    if (quickText) quickText.value = '';
                    showToast(data.message || 'Work logged!', 'success');
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    showToast(data.message || 'Failed to log work.', 'error');
                }
            }).catch(() => {
                loadingEl.classList.remove('wl-ai-loading--visible');
                quickSubmit.disabled = false;
                quickSubmit.textContent = 'Log with AI →';
                showToast('Network error. Please try again.', 'error');
            });
        });
    }

    // Copy yesterday
    if (copyYest) {
        copyYest.addEventListener('click', function () {
            apiFetch(ROUTES.yesterday, 'GET').then(log => {
                if (log && log.title) {
                    if (quickText) quickText.value = log.title + (log.description ? '. ' + log.description : '');
                    if (quickDate) quickDate.value = TODAY;
                    // Pre-select matching hour pill
                    if (log.duration_minutes && hourPills) {
                        const match = hourPills.querySelector('[data-minutes="' + log.duration_minutes + '"]');
                        if (match) {
                            hourPills.querySelectorAll('.wl-pill').forEach(p => p.classList.remove('wl-pill--active'));
                            match.classList.add('wl-pill--active');
                            selectedMinutes = log.duration_minutes;
                        }
                    }
                    if (log.output_value && outputPills) {
                        const opill = outputPills.querySelector('[data-output="' + log.output_value + '"]');
                        if (opill) {
                            outputPills.querySelectorAll('.wl-pill--output').forEach(p => p.classList.remove('wl-pill--active'));
                            opill.classList.add('wl-pill--active');
                            selectedOutput = log.output_value;
                        }
                    }
                    showToast('Yesterday\'s log copied!', 'info');
                } else {
                    showToast('No log found for yesterday.', 'info');
                }
            }).catch(() => showToast('Could not fetch yesterday\'s log.', 'error'));
        });
    }

    /* ─────────────────────────────────────────────────────────────────
       TAB 2: WEEKLY GRID
    ───────────────────────────────────────────────────────────────── */

    let weekOffset    = 0;
    let weeklyInitialized = false;

    function getMondayOfWeek(offset) {
        const d = new Date(TODAY + 'T00:00:00');
        const day = d.getDay(); // 0=Sun,1=Mon...
        const diff = (day === 0 ? -6 : 1 - day); // days to Monday
        d.setDate(d.getDate() + diff + offset * 7);
        return d;
    }

    function toDateStr(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function buildCategoryOptions(selected) {
        return Object.entries(CATEGORIES).map(([val, label]) =>
            '<option value="' + val + '"' + (val === (selected || '') ? ' selected' : '') + '>' + label + '</option>'
        ).join('');
    }

    function buildWeeklyRow(dateStr, isToday, rowIndex) {
        const d    = new Date(dateStr + 'T00:00:00');
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const dayName = days[d.getDay()];
        const dateShort = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return '<tr class="wl-weekly-row" data-date="' + dateStr + '">' +
            '<td class="wl-td wl-td--day' + (isToday ? ' wl-td--today' : '') + '">' +
                '<div class="wl-day-name">' + dayName + '</div>' +
                '<div class="wl-day-date-sub">' + dateShort + '</div>' +
            '</td>' +
            '<td class="wl-td">' +
                '<select class="wl-weekly-select wl-row-cat">' +
                    '<option value="">Category…</option>' + buildCategoryOptions('') +
                '</select>' +
            '</td>' +
            '<td class="wl-td">' +
                '<input type="text" class="wl-weekly-input wl-row-title" placeholder="What did you work on?" maxlength="255">' +
            '</td>' +
            '<td class="wl-td">' +
                '<input type="number" class="wl-weekly-num wl-row-mins" min="15" max="480" placeholder="60">' +
            '</td>' +
            '<td class="wl-td">' +
                '<input type="number" class="wl-weekly-num wl-row-output" min="1" max="10" placeholder="5">' +
            '</td>' +
            '<td class="wl-td">' +
                '<button class="wl-add-row-btn" data-date="' + dateStr + '" title="Add another row for this day">+</button>' +
            '</td>' +
        '</tr>';
    }

    function renderWeeklyGrid(existingLogs) {
        const monday  = getMondayOfWeek(weekOffset);
        const weekLabel = document.getElementById('wl-week-label');
        const body    = document.getElementById('wl-weekly-body');
        if (!body || !weekLabel) return;

        const days = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(monday);
            d.setDate(monday.getDate() + i);
            days.push(toDateStr(d));
        }

        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        weekLabel.textContent = formatDateLong(toDateStr(monday)) + ' – ' + formatDateLong(toDateStr(sunday));

        const logsByDate = {};
        (existingLogs || []).forEach(log => {
            const ld = log.log_date;
            if (!logsByDate[ld]) logsByDate[ld] = [];
            logsByDate[ld].push(log);
        });

        let html = '';
        days.forEach((dateStr, i) => {
            const isToday = dateStr === TODAY;
            // Existing log pills row (if any)
            const existing = logsByDate[dateStr] || [];
            if (existing.length) {
                const d = new Date(dateStr + 'T00:00:00');
                const days2 = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                const dayName = days2[d.getDay()];
                const dateShort = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                html += '<tr class="wl-existing-row">' +
                    '<td class="wl-td wl-td--day' + (isToday ? ' wl-td--today' : '') + '">' +
                        '<div class="wl-day-name">' + dayName + '</div>' +
                        '<div class="wl-day-date-sub">' + dateShort + '</div>' +
                    '</td>' +
                    '<td class="wl-td" colspan="5">' +
                        existing.map(l =>
                            '<span class="wl-existing-log-pill" title="' + l.title + '">' +
                            (CATEGORIES[l.category] || l.category) + ': ' + l.title +
                            (l.duration_minutes ? ' (' + Math.round(l.duration_minutes / 60 * 10) / 10 + 'h)' : '') +
                            '</span>'
                        ).join('') +
                    '</td>' +
                '</tr>';
            }
            html += buildWeeklyRow(dateStr, isToday, i);
        });
        body.innerHTML = html;

        // Add row buttons
        body.querySelectorAll('.wl-add-row-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const dateStr = this.dataset.date;
                const newRow  = document.createElement('tr');
                newRow.className = 'wl-weekly-row';
                newRow.dataset.date = dateStr;
                newRow.innerHTML = '<td class="wl-td"></td>' +
                    '<td class="wl-td"><select class="wl-weekly-select wl-row-cat"><option value="">Category…</option>' + buildCategoryOptions('') + '</select></td>' +
                    '<td class="wl-td"><input type="text" class="wl-weekly-input wl-row-title" placeholder="What did you work on?" maxlength="255"></td>' +
                    '<td class="wl-td"><input type="number" class="wl-weekly-num wl-row-mins" min="15" max="480" placeholder="60"></td>' +
                    '<td class="wl-td"><input type="number" class="wl-weekly-num wl-row-output" min="1" max="10" placeholder="5"></td>' +
                    '<td class="wl-td"></td>';
                this.closest('tr').after(newRow);
            });
        });
    }

    function initWeeklyGrid() {
        if (!document.getElementById('wl-weekly-body')) return;
        const monday = getMondayOfWeek(weekOffset);
        apiFetch(ROUTES.weekLogs + '?week_start=' + toDateStr(monday), 'GET')
            .then(logs => renderWeeklyGrid(logs || []))
            .catch(() => renderWeeklyGrid([]));
    }

    const weekPrev = document.getElementById('wl-week-prev');
    const weekNext = document.getElementById('wl-week-next');
    if (weekPrev) {
        weekPrev.addEventListener('click', function () {
            weekOffset--;
            initWeeklyGrid();
        });
    }
    if (weekNext) {
        weekNext.addEventListener('click', function () {
            weekOffset++;
            initWeeklyGrid();
        });
    }

    const bulkSubmit = document.getElementById('wl-bulk-submit');
    if (bulkSubmit) {
        bulkSubmit.addEventListener('click', function () {
            const rows  = document.querySelectorAll('#wl-weekly-body .wl-weekly-row');
            const logs  = [];
            rows.forEach(row => {
                const title  = row.querySelector('.wl-row-title')?.value.trim();
                const cat    = row.querySelector('.wl-row-cat')?.value;
                const mins   = parseInt(row.querySelector('.wl-row-mins')?.value, 10);
                const output = parseInt(row.querySelector('.wl-row-output')?.value, 10) || 5;
                if (title && cat && mins >= 15) {
                    logs.push({
                        log_date: row.dataset.date,
                        category: cat,
                        title: title,
                        duration_minutes: mins,
                        output_value: output,
                    });
                }
            });

            if (!logs.length) {
                showToast('Fill in at least one row with category, title, and duration.', 'error');
                return;
            }

            bulkSubmit.disabled   = true;
            bulkSubmit.textContent = 'Saving…';

            apiFetch(ROUTES.bulk, 'POST', { logs }).then(data => {
                bulkSubmit.disabled   = false;
                bulkSubmit.textContent = 'Save Week\'s Logs';
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    showToast(data.message || 'Failed to save logs.', 'error');
                }
            }).catch(() => {
                bulkSubmit.disabled   = false;
                bulkSubmit.textContent = 'Save Week\'s Logs';
                showToast('Network error. Please try again.', 'error');
            });
        });
    }

    /* ─────────────────────────────────────────────────────────────────
       TAB 3: TEMPLATES
    ───────────────────────────────────────────────────────────────── */

    function loadTemplates() {
        const grid  = document.getElementById('wl-templates-grid');
        const empty = document.getElementById('wl-templates-empty');
        if (!grid) return;

        apiFetch(ROUTES.templates, 'GET').then(templates => {
            if (!templates || !templates.length) {
                if (empty) empty.classList.remove('wl-hidden');
                return;
            }
            if (empty) empty.classList.add('wl-hidden');

            const cards = templates.map(t => {
                const cc = catClass(t.category);
                return '<div class="wl-template-card" data-id="' + t.id + '">' +
                    '<div class="wl-template-card-name">' + esc(t.name) + '</div>' +
                    '<div class="wl-template-card-meta">' +
                        '<span class="wl-cat-badge cat-badge-' + cc + '">' + (CATEGORIES[t.category] || t.category) + '</span>' +
                        (t.duration_minutes ? '<span class="wl-log-duration">' + Math.round(t.duration_minutes / 60 * 10) / 10 + 'h</span>' : '') +
                        (t.output_value ? '<span class="wl-log-output">★ ' + t.output_value + '/10</span>' : '') +
                    '</div>' +
                    '<div class="wl-template-card-title">' + esc(t.title) + '</div>' +
                    (t.usage_count ? '<div class="wl-template-usage">Used ' + t.usage_count + ' time' + (t.usage_count !== 1 ? 's' : '') + '</div>' : '') +
                    '<div class="wl-template-card-actions">' +
                        '<button class="wl-template-use-btn" data-id="' + t.id + '">Use Template</button>' +
                        '<button class="wl-template-delete-btn" data-id="' + t.id + '" title="Delete template">✕</button>' +
                    '</div>' +
                '</div>';
            }).join('');

            grid.innerHTML = '<div class="wl-templates-grid">' + cards + '</div>';

            // Use template
            grid.querySelectorAll('.wl-template-use-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    apiFetch(ROUTES.templateUseBase + '/' + id + '/use', 'POST').then(t => {
                        if (!t) return;
                        // Fill Quick Log and switch tab
                        if (quickText)  quickText.value  = t.title + (t.description ? '. ' + t.description : '');
                        if (quickDate)  quickDate.value  = TODAY;
                        // Select hour pill
                        if (t.duration_minutes && hourPills) {
                            const match = hourPills.querySelector('[data-minutes="' + t.duration_minutes + '"]');
                            if (match) {
                                hourPills.querySelectorAll('.wl-pill').forEach(p => p.classList.remove('wl-pill--active'));
                                match.classList.add('wl-pill--active');
                                selectedMinutes = t.duration_minutes;
                                customInput.classList.add('wl-hidden');
                            } else {
                                // Use custom
                                const customPill = hourPills.querySelector('[data-minutes="custom"]');
                                if (customPill) {
                                    hourPills.querySelectorAll('.wl-pill').forEach(p => p.classList.remove('wl-pill--active'));
                                    customPill.classList.add('wl-pill--active');
                                }
                                customInput.classList.remove('wl-hidden');
                                customInput.value = t.duration_minutes;
                                selectedMinutes = t.duration_minutes;
                            }
                        }
                        // Select output pill
                        if (t.output_value && outputPills) {
                            const opill = outputPills.querySelector('[data-output="' + t.output_value + '"]');
                            if (opill) {
                                outputPills.querySelectorAll('.wl-pill--output').forEach(p => p.classList.remove('wl-pill--active'));
                                opill.classList.add('wl-pill--active');
                                selectedOutput = t.output_value;
                            }
                        }
                        switchTab('quicklog');
                        if (quickText) quickText.focus();
                        showToast('Template loaded — edit and submit!', 'info');
                    }).catch(() => showToast('Could not load template.', 'error'));
                });
            });

            // Delete template
            grid.querySelectorAll('.wl-template-delete-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (!confirm('Delete this template?')) return;
                    const id = this.dataset.id;
                    apiFetch(ROUTES.templateUseBase + '/' + id, 'DELETE').then(data => {
                        if (data.success) {
                            showToast('Template deleted.', 'success');
                            loadTemplates();
                        }
                    }).catch(() => showToast('Could not delete template.', 'error'));
                });
            });
        }).catch(() => showToast('Could not load templates.', 'error'));
    }

    // Save template form
    const saveTemplateBtn = document.getElementById('wl-save-template');
    if (saveTemplateBtn) {
        saveTemplateBtn.addEventListener('click', function () {
            const name    = document.getElementById('tpl-name')?.value.trim();
            const cat     = document.getElementById('tpl-category')?.value;
            const title   = document.getElementById('tpl-title')?.value.trim();
            const desc    = document.getElementById('tpl-description')?.value.trim();
            const dur     = parseInt(document.getElementById('tpl-duration')?.value, 10) || null;
            const output  = parseInt(document.getElementById('tpl-output')?.value, 10)   || 5;

            if (!name)  { showToast('Please enter a template name.', 'error'); return; }
            if (!cat)   { showToast('Please select a category.', 'error'); return; }
            if (!title) { showToast('Please enter a default title.', 'error'); return; }

            saveTemplateBtn.disabled   = true;
            saveTemplateBtn.textContent = 'Saving…';

            apiFetch(ROUTES.templateStore, 'POST', {
                name, category: cat, title, description: desc,
                duration_minutes: dur, output_value: output,
            }).then(data => {
                saveTemplateBtn.disabled   = false;
                saveTemplateBtn.textContent = 'Save Template';
                if (data.success) {
                    showToast(data.message, 'success');
                    // Reset form
                    ['tpl-name','tpl-title','tpl-description','tpl-duration'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    const catEl = document.getElementById('tpl-category');
                    if (catEl) catEl.value = '';
                    const outEl = document.getElementById('tpl-output');
                    if (outEl) outEl.value = '';
                    loadTemplates();
                } else {
                    showToast(data.message || 'Failed to save template.', 'error');
                }
            }).catch(() => {
                saveTemplateBtn.disabled   = false;
                saveTemplateBtn.textContent = 'Save Template';
                showToast('Network error. Please try again.', 'error');
            });
        });
    }

    /* ─────────────────────────────────────────────────────────────────
       DELETE FORM CONFIRMATION (history timeline)
    ───────────────────────────────────────────────────────────────── */

    document.querySelectorAll('.wl-delete-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Delete this log entry?')) {
                e.preventDefault();
            }
        });
    });

    /* ─────────────────────────────────────────────────────────────────
       UTILITY
    ───────────────────────────────────────────────────────────────── */

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

});
