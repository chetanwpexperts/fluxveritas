document.addEventListener('DOMContentLoaded', function () {

    /* Animate pulse bars on load */
    document.querySelectorAll('.ud-pulse-fill').forEach(function (bar) {
        var width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function () {
            bar.style.transition = 'width 0.8s ease';
            bar.style.width = width;
        }, 200);
    });

    /* Ripple effect on module cards */
    document.querySelectorAll('.ud-module-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            var ripple = document.createElement('span');
            ripple.style.cssText = [
                'position:absolute',
                'border-radius:50%',
                'background:rgba(0,0,0,0.06)',
                'transform:scale(0)',
                'animation:ripple 0.4s linear',
                'pointer-events:none',
                'width:40px',
                'height:40px',
                'left:' + (e.offsetX - 20) + 'px',
                'top:' + (e.offsetY - 20) + 'px',
            ].join(';');
            this.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 400);
        });
    });

});
