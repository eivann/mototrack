(function() {
    // Initialize Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Theme Toggle Logic
    const htmlRoot = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    function applyLandingTheme(isDark) {
        if (isDark) {
            htmlRoot.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            if (themeIcon) themeIcon.setAttribute('data-lucide', 'sun');
        } else {
            htmlRoot.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            if (themeIcon) themeIcon.setAttribute('data-lucide', 'moon');
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    const savedTheme = localStorage.getItem('theme');
    applyLandingTheme(savedTheme !== 'light');

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            applyLandingTheme(!htmlRoot.classList.contains('dark'));
        });
    }

    // Navbar scroll effect
    const nav = document.getElementById('navbar');
    if (nav) {
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // Scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const href = a.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const t = document.querySelector(href);
            if (t) t.scrollIntoView({ behavior: 'smooth' });
        });
    });
})();
