const nav = document.getElementById('lnav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });

function toggleMobileMenu() {
    document.getElementById('lnav-mobile').classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.lnav-ham') && !e.target.closest('#lnav-mobile')) {
        document.getElementById('lnav-mobile').classList.remove('open');
    }
});

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('in');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

document.querySelectorAll('.lanim').forEach(el => observer.observe(el));
