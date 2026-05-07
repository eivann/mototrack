<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
{{-- Apply theme before paint to avoid flash --}}
<script>
    (function() {
        var theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
<title>MotoTrack — Edmar Motorcycle Parts & Accessories</title>
<meta name="description" content="Your trusted motorcycle parts shop in Pandi, Bulacan since 2009. Browse parts, check compatibility, and pre-order online.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=1.1">
</head>
<body>

{{-- NAVBAR --}}
<nav class="land-nav" id="navbar">
    <div class="nav-brand">
        <img src="{{ asset('images/logo.png') }}" alt="EM Parts Logo" style="height:50px;width:auto;margin-right:15px">
        <div><h1 style="font-size:1.5rem;letter-spacing:-1px">EDMAR MOTORCYCLE</h1><p style="color:#9ca3af">Parts and Accessories</p></div>
    </div>
    <ul class="nav-links">
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#features">Services</a></li>
        <li><a href="#compat">Compatibility</a></li>
        <li><a href="#preorder">Pre-Order</a></li>
        <li><a href="#contact">Contact</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-toggle" id="themeToggle" title="Toggle Light/Dark Mode">
            <i data-lucide="sun" id="themeIcon"></i>
        </button>
        @guest
            <a href="{{ route('login') }}" class="btn btn-ghost">Sign In</a>
            <a href="{{ route('register') }}" class="btn btn-glow">Get Started</a>
        @endguest
        @auth
            <a href="/customer-dashboard" class="btn btn-glow">Dashboard</a>
        @endauth
    </div>
</nav>

{{-- HERO --}}
<section class="hero" id="home">
    <div class="hero-inner">
        <div>
            <div class="hero-badge"><span class="dot"></span> Serving Pandi, Bulacan since 2009</div>
            <h1>Your <span>Trusted Partner</span> for Motorcycle Parts</h1>
            <p class="hero-desc">Find genuine parts, check compatibility instantly, and get expert repair services — all in one place.</p>
            <div class="hero-btns">
                <a href="/browse" class="btn btn-glow"><i data-lucide="shopping-bag" style="width:18px;height:18px"></i> Browse Products</a>
                <a href="#compat" class="btn btn-ghost"><i data-lucide="search" style="width:18px;height:18px"></i> Check Compatibility</a>
            </div>
        </div>
        <div class="hero-img">
            <img src="{{ asset('images/hero.png') }}" alt="Motorcycle Parts Shop">
            <div class="hero-float">
                <div class="icon">✔</div>
                <div><p>Open Daily</p><strong>6:00 AM — 6:00 PM</strong></div>
            </div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section class="section" id="features">
    <div class="section-inner fade-up">
        <div style="text-align:center">
            <div class="section-tag"><i data-lucide="zap" style="width:14px;height:14px"></i> Our Services</div>
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-desc" style="margin:0 auto">From parts to repairs, we've got your motorcycle covered with professional service and quality products.</p>
        </div>
        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon fi-blue"><i data-lucide="box" style="width:24px;height:24px"></i></div>
                <h3>Quality Parts</h3>
                <p>Wide selection of genuine motorcycle parts and accessories for all major brands.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon fi-purple"><i data-lucide="search" style="width:24px;height:24px"></i></div>
                <h3>Smart Compatibility</h3>
                <p>Instantly check which parts fit your motorcycle model with our smart search tool.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon fi-green"><i data-lucide="wrench" style="width:24px;height:24px"></i></div>
                <h3>Expert Repairs</h3>
                <p>Professional repair services by experienced mechanics with fast turnaround.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon fi-amber"><i data-lucide="clipboard-check" style="width:24px;height:24px"></i></div>
                <h3>Pre-Order System</h3>
                <p>Can't find what you need? Pre-order any part with just 50% down payment.</p>
            </div>
        </div>
    </div>
</section>

{{-- ABOUT --}}
<section class="section" id="about">
    <div class="section-inner fade-up">
        <div class="about-grid">
            <div class="about-img">
                <img src="{{ asset('images/about.png') }}" alt="Expert Mechanics">
            </div>
            <div>
                <div class="section-tag"><i data-lucide="info" style="width:14px;height:14px"></i> About Us</div>
                <h2 class="section-title">Trusted by Riders Across Bulacan</h2>
                <p class="section-desc">Since 2009, Edmar Motorcycle Parts has been the go-to shop for riders in Pandi and beyond. We combine quality products with expert service to keep you on the road.</p>
                <div class="about-checks">
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> Genuine OEM Parts</div>
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> Expert Mechanics</div>
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> Fast Turnaround</div>
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> Fair Pricing</div>
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> Gcash Payments</div>
                    <div class="about-check"><i data-lucide="check-circle" style="width:18px;height:18px"></i> 15+ Years Experience</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- COMPATIBILITY --}}
<section class="section" id="compat">
    <div class="section-inner fade-up" style="text-align:center">
        <div class="section-tag"><i data-lucide="search" style="width:14px;height:14px"></i> Compatibility Checker</div>
        <h2 class="section-title">Find the Right Part</h2>
        <p class="section-desc" style="margin:0 auto">Enter your motorcycle details and we'll show you compatible parts instantly.</p>
        <div class="compat-card" style="text-align:left">
            <form method="GET" action="/compatibility">
                <input name="make" placeholder="Motorcycle Make (e.g. Honda, Yamaha)">
                <input name="model" placeholder="Model (e.g. Click 125i, Mio)">
                <input name="part" placeholder="Part you're looking for">
                <button type="submit" class="btn btn-glow"><i data-lucide="search" style="width:18px;height:18px"></i> Check Compatibility</button>
            </form>
        </div>
    </div>
</section>

{{-- PREORDER --}}
<section class="section" id="preorder">
    <div class="section-inner fade-up">
        <div style="text-align:center;margin-bottom:8px">
            <div class="section-tag"><i data-lucide="package" style="width:14px;height:14px"></i> Pre-Order</div>
            <h2 class="section-title">Can't Find Your Part?</h2>
            <p class="section-desc" style="margin:0 auto">We'll source it for you. Just place a pre-order with 50% down payment and we'll handle the rest.</p>
        </div>
        <div class="preorder-grid">
            <div class="po-steps">
                <div class="po-step"><div class="po-step-num">1</div><div><h4>Submit Pre-Order</h4><p>Fill out the form with the part details you need</p></div></div>
                <div class="po-step"><div class="po-step-num">2</div><div><h4>Pay 50% Down Payment</h4><p>We'll send Gcash payment details via SMS</p></div></div>
                <div class="po-step"><div class="po-step-num">3</div><div><h4>We Process Your Order</h4><p>Your part will be ordered and reserved for you</p></div></div>
                <div class="po-step"><div class="po-step-num">4</div><div><h4>Pick Up & Pay Balance</h4><p>Pay the remaining 50% when you pick up at our shop</p></div></div>
            </div>
            <div class="po-info">
                <h3>Payment & Pickup Info</h3>
                <ul style="list-style:none;padding:0">
                    <li><i data-lucide="check" style="width:16px;height:16px;min-width:16px"></i><span><b>Pre-Order:</b> 50% down payment required via Gcash</span></li>
                    <li><i data-lucide="check" style="width:16px;height:16px;min-width:16px"></i><span><b>Available Products:</b> 100% payment required for checkout</span></li>
                    <li><i data-lucide="check" style="width:16px;height:16px;min-width:16px"></i><span><b>Payment:</b> Gcash Payment only accepted</span></li>
                    <li><i data-lucide="check" style="width:16px;height:16px;min-width:16px"></i><span><b>Delivery:</b> Pick up only at our shop in Pandi, Bulacan</span></li>
                    <li><i data-lucide="check" style="width:16px;height:16px;min-width:16px"></i><span><b>Shop Hours:</b> Open daily from 6:00 AM to 6:00 PM</span></li>
                </ul>
                <div style="margin-top:24px">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-glow" style="width:100%;justify-content:center">Create Account to Pre-Order</a>
                    @endguest
                    @auth
                        <a href="/preorder-custom" class="btn btn-glow" style="width:100%;justify-content:center">Submit a Pre-Order</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CONTACT --}}
<section class="section" id="contact">
    <div class="section-inner fade-up">
        <div style="text-align:center;margin-bottom:8px">
            <div class="section-tag"><i data-lucide="mail" style="width:14px;height:14px"></i> Get In Touch</div>
            <h2 class="section-title">Visit Our Shop</h2>
            <p class="section-desc" style="margin:0 auto">We'd love to help you find the right parts. Visit us or get in touch!</p>
        </div>
        <div class="contact-grid">
            <div class="contact-cards">
                <div class="contact-card"><div class="ci"><i data-lucide="map-pin" style="width:20px;height:20px"></i></div><div><h4>Location</h4><p>Pandi, Bulacan, Philippines</p></div></div>
                <div class="contact-card"><div class="ci"><i data-lucide="phone" style="width:20px;height:20px"></i></div><div><h4>Phone</h4><p>+63 XXX XXX XXXX</p></div></div>
                <div class="contact-card"><div class="ci"><i data-lucide="mail" style="width:20px;height:20px"></i></div><div><h4>Email</h4><p>contact@edmarmoto.ph</p></div></div>
                <div class="contact-card"><div class="ci"><i data-lucide="clock" style="width:20px;height:20px"></i></div><div><h4>Shop Hours</h4><p>Open Daily: 6:00 AM — 6:00 PM</p></div></div>
            </div>
            <div class="map-wrap">
                @if(config('services.google_maps.key'))
                    <iframe width="100%" height="450" style="border:0;" loading="lazy" allowfullscreen 
                        src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google_maps.key') }}&q=Edmar+Motorcycle+Parts,+Pandi,+Bulacan,+Philippines"></iframe>
                @else
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:rgba(255,255,255,0.3)">
                        <p>Map will appear once API key is configured.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="land-footer">
    <div class="footer-inner">
        <div>
            <div class="nav-brand"><div class="nav-logo">M</div><div><h1>MOTOTRACK</h1><p>Edmar Motorcycle Parts</p></div></div>
            <p class="footer-brand">Your trusted partner for quality motorcycle parts and expert repair services in Pandi, Bulacan since 2009.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Services</a></li>
                <li><a href="#compat">Compatibility</a></li>
                <li><a href="#preorder">Pre-Order</a></li>
            </ul>
        </div>
        <div>
            <h4>Contact</h4>
            <ul>
                <li><span>Pandi, Bulacan</span></li>
                <li><span>+63 XXX XXX XXXX</span></li>
                <li><span>contact@edmarmoto.ph</span></li>
                <li><span>Daily: 6AM — 6PM</span></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Edmar Motorcycle Parts & Accessories. All rights reserved.</p>
        <p>Powered by MOTOTRACK</p>
    </div>
</footer>

<script>
lucide.createIcons();

// Theme Toggle Logic
const htmlRoot = document.getElementById('html-root');
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

function applyLandingTheme(isDark) {
    console.log('Landing Theme Change:', isDark ? 'DARK' : 'LIGHT');
    if (isDark) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        if (themeIcon) themeIcon.setAttribute('data-lucide', 'sun');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        if (themeIcon) themeIcon.setAttribute('data-lucide', 'moon');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

const savedTheme = localStorage.getItem('theme');
console.log('Landing Saved Theme:', savedTheme);
applyLandingTheme(savedTheme !== 'light');

themeToggle.addEventListener('click', () => {
    applyLandingTheme(!htmlRoot.classList.contains('dark'));
});

// Navbar scroll effect
const nav = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 50);
});

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
</script>

</body>
</html>
