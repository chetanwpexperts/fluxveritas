/*
 * Billing UI: period toggle, Razorpay checkout, confirm dialogs.
 *
 * Markup contract:
 *   [data-checkout]                root, with data-order-url / data-verify-url / data-failed-url / data-after-pay-url
 *     input[name=period]           monthly | yearly radios
 *     [data-period-panel=monthly]  shown for the selected period (optional)
 *     [data-pay]                   pay button; data-label-monthly / data-label-yearly
 *     [data-checkout-error]        inline error box
 *   [data-dialog-open="id"]        opens <dialog id="id">; [data-dialog-close] closes it
 *
 * After a verified payment the browser goes to data-after-pay-url: Billing shows
 * the new plan, a locked feature's URL opens the feature itself.
 */
(function () {
    'use strict';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(body),
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) {
                    var message = data.message;
                    if (res.status === 419) message = 'Your session expired. Refresh the page and try again.';
                    throw new Error(message || 'Something went wrong. Please try again.');
                }
                return data;
            });
        });
    }

    function initCheckout(root) {
        var payBtn = root.querySelector('[data-pay]');
        var errorBox = root.querySelector('[data-checkout-error]');
        if (!payBtn) return;

        function selectedPeriod() {
            var checked = root.querySelector('input[name="period"]:checked');
            return checked ? checked.value : 'yearly';
        }

        function showError(message) {
            if (!errorBox) return;
            errorBox.textContent = message || '';
            errorBox.classList.toggle('is-visible', !!message);
        }

        function syncPeriod() {
            var period = selectedPeriod();
            root.querySelectorAll('[data-period-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-period-panel') !== period;
            });
            var label = payBtn.getAttribute('data-label-' + period);
            if (label) payBtn.textContent = label;
            showError('');
        }

        function setBusy(busy, text) {
            payBtn.disabled = busy;
            if (busy) {
                payBtn.textContent = text;
            } else {
                syncPeriod();
            }
        }

        root.querySelectorAll('input[name="period"]').forEach(function (input) {
            input.addEventListener('change', syncPeriod);
        });
        syncPeriod();

        payBtn.addEventListener('click', function () {
            var period = selectedPeriod();
            showError('');
            setBusy(true, 'Opening secure checkout…');

            post(root.dataset.orderUrl, { period: period }).then(function (order) {
                if (typeof window.Razorpay === 'undefined') {
                    throw new Error('The payment window could not load. Check your connection (or turn off ad blockers) and try again.');
                }

                var rzp = new window.Razorpay({
                    key: order.key,
                    amount: order.amount,
                    currency: order.currency,
                    name: order.name,
                    description: order.description,
                    order_id: order.order_id,
                    prefill: order.prefill,
                    theme: { color: '#18181b' },
                    handler: function (response) {
                        setBusy(true, 'Confirming payment…');
                        post(root.dataset.verifyUrl, response).then(function () {
                            window.location.assign(root.dataset.afterPayUrl || window.location.href);
                        }).catch(function (err) {
                            setBusy(false);
                            showError(err.message);
                        });
                    },
                    modal: {
                        ondismiss: function () { setBusy(false); },
                    },
                });

                rzp.on('payment.failed', function (resp) {
                    var reason = (resp && resp.error && resp.error.description) || 'The payment did not go through';
                    post(root.dataset.failedUrl, { razorpay_order_id: order.order_id, reason: reason }).catch(function () {});
                    showError(reason + '. You can try again with the same or another payment method.');
                });

                rzp.open();
            }).catch(function (err) {
                setBusy(false);
                showError(err.message);
            });
        });
    }

    function initDialogs() {
        document.querySelectorAll('[data-dialog-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = document.getElementById(btn.getAttribute('data-dialog-open'));
                if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
            });
        });
        document.querySelectorAll('[data-dialog-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dialog = btn.closest('dialog');
                if (dialog) dialog.close();
            });
        });
        // Prevent double submits on confirm forms
        document.querySelectorAll('form[data-confirm-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('[type="submit"]');
                if (submit) { submit.disabled = true; submit.textContent = 'Working…'; }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-checkout]').forEach(initCheckout);
        initDialogs();
    });
})();
