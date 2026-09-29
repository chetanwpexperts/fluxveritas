// ── Particles ───────────────────────────────────────────────────────────────
function createParticles(containerId, count) {
    const container = document.getElementById(containerId);
    if (!container) return;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const colors = ['#22c55e','#3b82f6','#a855f7','rgba(255,255,255,0.5)'];
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            width: ${2 + Math.random() * 4}px;
            height: ${2 + Math.random() * 4}px;
            opacity: ${0.2 + Math.random() * 0.5};
            animation-duration: ${4 + Math.random() * 8}s;
            animation-delay: ${Math.random() * 4}s;
            background: ${colors[Math.floor(Math.random()*4)]};
        `;
        container.appendChild(p);
    }
}
createParticles('particles-0', 30);

// ── Typewriter ──────────────────────────────────────────────────────────────
const typeText = 'The Platform That Shows The Truth';
const typeEl = document.getElementById('typewriter-text');
let ti = 0;

function typeNext() {
    if (ti <= typeText.length) {
        typeEl.textContent = typeText.slice(0, ti);
        ti++;
        setTimeout(typeNext, 60 + Math.random() * 40);
    }
}
setTimeout(typeNext, 1200);

// ── Sections list (dynamic — supports non-numeric IDs) ──────────────────────
const numericSections = Array.from({ length: 17 }, (_, i) => document.getElementById('section-' + i));
const dots = document.querySelectorAll('.tour-dot');

function scrollToSection(i) {
    numericSections[i]?.scrollIntoView({ behavior: 'smooth' });
}

// ── Event delegation: dots and role-cards ───────────────────────────────────
document.addEventListener('click', function(e) {
    var dot = e.target.closest('[data-section]');
    if (dot) {
        var idx = parseInt(dot.dataset.section, 10);
        if (!isNaN(idx)) scrollToSection(idx);
        return;
    }
    var btn = e.target.closest('.faq-q');
    if (btn) {
        var item = btn.closest('.faq-item');
        var wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(function(el) { el.classList.remove('open'); });
        if (!wasOpen) item.classList.add('open');
    }
});

// ── Progress bar + dots ─────────────────────────────────────────────────────
function updateProgress() {
    const scrollTop = window.scrollY;
    const docH = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docH > 0 ? (scrollTop / docH) * 100 : 0;
    document.getElementById('tour-progress').style.width = pct + '%';

    let activeIdx = 0;
    numericSections.forEach((sec, i) => {
        if (sec) {
            const rect = sec.getBoundingClientRect();
            if (rect.top <= window.innerHeight * 0.5) activeIdx = i;
        }
    });
    dots.forEach((d, i) => d.classList.toggle('active', i === activeIdx));
}

window.addEventListener('scroll', updateProgress, { passive: true });

// ── Intersection Observer ───────────────────────────────────────────────────
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('revealed');
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

document.querySelectorAll('.anim-hidden').forEach(el => revealObs.observe(el));
