document.addEventListener('DOMContentLoaded', function () {

    /* ===== AUTO-SUBMIT SELECT ON CHANGE ===== */
    document.addEventListener('change', function (e) {
        const select = e.target.closest('[data-auto-submit]');
        if (select) {
            const form = select.closest('form');
            if (form) form.submit();
        }
    });

    /* ===== CONFIRM BEFORE FORM SUBMIT ===== */
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm]');
        if (form) {
            const msg = form.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) {
                e.preventDefault();
            }
        }
    });

    /* ===== SKILLS INPUT TAG DISPLAY ===== */
    const skillsInput = document.getElementById('skills-input');
    if (skillsInput) {
        skillsInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    }

});
