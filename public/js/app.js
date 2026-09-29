// Flash message auto-dismiss
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('[data-flash-message]').forEach(function(el) {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        });
    }, 4000);
});

/* ===== BLOCKER FORM ===== */
document.addEventListener('DOMContentLoaded', function() {
    var blockingUserSelect = document.getElementById('blocker-blocking-user-select');
    var notifyName = document.getElementById('blocker-notify-name');

    if (blockingUserSelect && notifyName) {
        blockingUserSelect.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            if (selected && selected.value) {
                notifyName.textContent = selected.text;
            } else {
                notifyName.textContent = 'Select a team member above';
            }
        });
    }
});
