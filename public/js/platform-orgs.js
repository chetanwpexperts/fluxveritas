/*
 * Settings → Platform → Organizations: suspend / activate confirmation dialogs.
 *
 * Buttons: data-org-action="suspend|activate" data-url="…" data-org-name="…"
 * Dialogs: #suspend-org-dialog / #activate-org-dialog, each with one <form>;
 * elements with [data-org-name] inside a dialog receive the organization name.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-org-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = document.getElementById(btn.dataset.orgAction + '-org-dialog');
                if (!dialog || typeof dialog.showModal !== 'function') return;

                var form = dialog.querySelector('form');
                form.action = btn.dataset.url;
                form.reset();
                dialog.querySelectorAll('[data-org-name]').forEach(function (el) {
                    el.textContent = btn.dataset.orgName;
                });

                dialog.showModal();
                var field = dialog.querySelector('textarea');
                if (field) field.focus();
            });
        });

        document.querySelectorAll('[data-dialog-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('dialog').close();
            });
        });

        // One submit only
        document.querySelectorAll('.po-dialog form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('[type="submit"]');
                if (submit) { submit.disabled = true; submit.textContent = 'Working…'; }
            });
        });
    });
})();
