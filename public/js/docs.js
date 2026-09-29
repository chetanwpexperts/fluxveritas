// ── Sidebar active link ──────────────────────────────────────────────────────
const navLinks = document.querySelectorAll('.nav-link');
const sections = document.querySelectorAll('[id]');
const sectionLabel = document.getElementById('current-section-label');

function updateActiveLink() {
    let current = '';
    sections.forEach(sec => {
        if (sec.getBoundingClientRect().top <= 100) current = sec.id;
    });
    navLinks.forEach(link => {
        const href = link.getAttribute('href').replace('#','');
        link.classList.toggle('active', href === current);
        if (href === current && link.textContent) {
            sectionLabel.textContent = link.textContent.trim();
        }
    });

    const scrollTop = window.scrollY;
    const docH = document.documentElement.scrollHeight - window.innerHeight;
    document.getElementById('docs-progress').style.width = (docH > 0 ? (scrollTop/docH)*100 : 0) + '%';
}

window.addEventListener('scroll', updateActiveLink, { passive: true });

// ── FAQ toggle (event delegation — no onclick in HTML) ────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.faq-q');
    if (!btn) return;
    const item = btn.closest('.faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(el => el.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
});

// ── Feedback buttons (event delegation — no onclick in HTML) ────────────────
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-feedback]');
    if (!btn) return;
    if (btn.dataset.feedback === 'yes') {
        btn.textContent = '👍 Thanks!';
        btn.classList.remove('docs-cta-btn-yes');
        btn.classList.add('docs-cta-btn-yes-active');
    } else {
        btn.textContent = '👎 Got it';
        btn.classList.remove('docs-cta-btn-no');
        btn.classList.add('docs-cta-btn-no-active');
    }
});

// ── Sidebar search (event delegation — no oninput in HTML) ───────────────────
var searchInput = document.getElementById('sidebar-search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        navLinks.forEach(function(link) {
            link.style.display = (link.textContent.toLowerCase().includes(q) || !q) ? '' : 'none';
        });
    });
}
