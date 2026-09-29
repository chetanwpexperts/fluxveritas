(function () {
    const startInput  = document.getElementById('start_date');
    const endInput    = document.getElementById('end_date');
    const display     = document.getElementById('duration-display');
    const durationTxt = document.getElementById('duration-text');

    startInput.addEventListener('change', function () {
        if (this.value && !endInput.value) {
            const d = new Date(this.value);
            d.setDate(d.getDate() + 14);
            endInput.value = d.toISOString().split('T')[0];
        }
        updateDuration();
    });

    endInput.addEventListener('change', updateDuration);

    function updateDuration() {
        if (startInput.value && endInput.value) {
            const start = new Date(startInput.value);
            const end   = new Date(endInput.value);
            const days  = Math.round((end - start) / (1000 * 60 * 60 * 24));
            if (days > 0) {
                const weeks = Math.floor(days / 7);
                const rem   = days % 7;
                let txt = days + ' days';
                if (weeks > 0) {
                    txt += ' (' + weeks + (rem > 0 ? '.' + Math.round(rem / 7 * 10) : '') + ' weeks)';
                }
                durationTxt.textContent = txt;
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        }
    }
})();
