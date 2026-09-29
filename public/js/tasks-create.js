// ── Type radio ────────────────────────────────────────────────────────────────
document.querySelectorAll('.type-radio').forEach(radio => {
    if (radio.checked) {
        radio.nextElementSibling.style.background  = '#f4f4f5';
        radio.nextElementSibling.style.borderColor = '#18181b';
        radio.nextElementSibling.style.color       = '#09090b';
    }
    radio.addEventListener('change', function() {
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.style.background  = 'white';
            btn.style.borderColor = '#e4e4e7';
            btn.style.color       = '';
        });
        this.nextElementSibling.style.background  = '#f4f4f5';
        this.nextElementSibling.style.borderColor = '#18181b';
        this.nextElementSibling.style.color       = '#09090b';
    });
});
document.querySelectorAll('.type-btn').forEach(span => {
    span.addEventListener('click', function() {
        const radio = this.previousElementSibling;
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
});

// ── Priority radio ────────────────────────────────────────────────────────────
document.querySelectorAll('.priority-radio').forEach(radio => {
    if (radio.checked) {
        radio.nextElementSibling.style.borderColor = '#18181b';
        radio.nextElementSibling.style.background  = '#f4f4f5';
        radio.nextElementSibling.style.color       = '#09090b';
    }
    radio.addEventListener('change', function() {
        document.querySelectorAll('.priority-option').forEach(opt => {
            opt.style.borderColor = '#e4e4e7';
            opt.style.background  = 'white';
            opt.style.color       = '#71717a';
        });
        this.nextElementSibling.style.borderColor = '#18181b';
        this.nextElementSibling.style.background  = '#f4f4f5';
        this.nextElementSibling.style.color       = '#09090b';
    });
});

document.querySelectorAll('.priority-option').forEach(div => {
    div.addEventListener('click', function() {
        const radio = this.previousElementSibling;
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
    });
});
