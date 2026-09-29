document.addEventListener('DOMContentLoaded', function () {
    var loader = document.getElementById('page-loader');
    if (!loader) return;

    function hideLoader() {
        loader.classList.add('fade-out');
        setTimeout(function () {
            loader.style.display = 'none';
        }, 200);
    }

    window.addEventListener('load', function () {
        setTimeout(hideLoader, 100);
    });

    // Safety fallback — force hide after 2 seconds
    setTimeout(function () {
        if (loader && !loader.classList.contains('fade-out')) {
            hideLoader();
        }
    }, 2000);
});

// Add fonts-loaded class when fonts are ready
if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
        document.documentElement.classList.add('fonts-loaded');
    });
} else {
    setTimeout(function () {
        document.documentElement.classList.add('fonts-loaded');
    }, 500);
}
