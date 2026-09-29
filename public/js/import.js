/*
 * Smart Import pages.
 * - Mapping: highlights fields chosen for more than one column.
 * - Progress: polls data-progress-url and updates the bar; reloads when finished.
 */
(function () {
    'use strict';

    function initMapping(form) {
        var selects = form.querySelectorAll('select[data-mapping]');
        function check() {
            var counts = {};
            selects.forEach(function (s) { if (s.value) counts[s.value] = (counts[s.value] || 0) + 1; });
            var clash = false;
            selects.forEach(function (s) {
                var dup = s.value && counts[s.value] > 1;
                s.classList.toggle('is-duplicate', dup);
                clash = clash || dup;
            });
            var warn = form.querySelector('[data-duplicate-warning]');
            if (warn) warn.classList.toggle('im-hidden', !clash);
        }
        selects.forEach(function (s) {
            s.addEventListener('change', function () { s.classList.remove('is-suggested'); check(); });
        });
        check();
    }

    function initProgress(box) {
        var bar = box.querySelector('[data-progress-bar]');
        var label = box.querySelector('[data-progress-label]');
        var url = box.getAttribute('data-progress-url');

        function poll() {
            fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    bar.value = d.percent;
                    label.textContent = d.processed.toLocaleString() + ' of ' + d.total.toLocaleString() + ' rows (' + d.percent + '%)';
                    if (d.finished) { window.location.reload(); return; }
                    setTimeout(poll, 1500);
                })
                .catch(function () { setTimeout(poll, 4000); });
        }
        poll();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-import-mapping]').forEach(initMapping);
        document.querySelectorAll('[data-progress-url]').forEach(initProgress);
    });
})();
