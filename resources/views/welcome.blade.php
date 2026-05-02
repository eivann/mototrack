<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MotoTrack</title>

<script src="https://cdn.tailwindcss.com"></script>

<script src="https://unpkg.com/lucide@latest"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: radial-gradient(circle at top, #eef2ff, #f8fafc);
}

.section {
    padding: 100px 40px;
}

.container {
    max-width: 1200px;
    margin: auto;
}

.card {
    background: white;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 25px 60px rgba(0,0,0,0.08);
}

.btn {
    border-radius: 14px;
    padding: 12px 22px;
    font-weight: 500;
    transition: 0.25s;
}

.btn-primary {
    background: linear-gradient(135deg,#2563eb,#4f46e5);
    color: white;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(37,99,235,0.3);
}

.glass {
    backdrop-filter: blur(14px);
    background: rgba(255,255,255,0.7);
}
.btn-outline {
    border: 1px solid #4f46e5;
    color: #4f46e5;
    background: transparent;
}
.btn-outline:hover {
    background: #4f46e5;
    color: white;
}
</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="glass border-b flex justify-between items-center px-10 py-5 sticky top-0 z-50">

<div class="flex items-center gap-3">
<div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-xl font-bold">M</div>
<div>
<h1 class="font-semibold">MOTOTRACK</h1>
<p class="text-xs text-gray-500">Edmar Motorcycle Parts</p>
</div>
</div>

<div class="hidden md:flex gap-8 text-sm text-gray-600">
<a href="#">Home</a>
<a href="#about">About</a>
<a href="#compat">Compatibility</a>
<a href="#preorder">Pre-Order</a>
<a href="#contact">Contact</a>
</div>

<div class="flex gap-3">
@guest
    <a href="{{ route('login') }}" class="btn btn-primary">Sign In</a>
    <a href="{{ route('register') }}" class="btn btn-outline">Sign Up</a>
@endguest

@auth
    <a href="/customer-dashboard" class="btn btn-primary">Dashboard</a>
@endauth
</div>
</nav>

<!-- HERO -->

<section class="section container grid md:grid-cols-2 gap-16 items-center">

<div>
<span class="bg-blue-100 text-blue-600 px-4 py-2 rounded-full text-sm">
Serving Pandi since 2009
</span>

<h1 class="text-5xl font-extrabold mt-6 leading-tight">
Your <span class="text-blue-600">Trusted Partner</span><br>
for Motorcycle Parts
</h1>

<p class="text-gray-600 mt-4">
Find parts, check compatibility instantly, and get expert services.
</p>

<div class="flex gap-4 mt-6">
<a href="browse" class="btn btn-primary">Browse Products</a>
<a href="#compat" class="btn border border-blue-600 text-blue-600">Check Compatibility</a>
</div>

</div>

<div class="relative">
<div class="bg-white/50 backdrop-blur-xl h-[400px] rounded-3xl flex items-center justify-center">
<i data-lucide="package" class="w-16 h-16 text-blue-400"></i>
</div>

<div class="absolute bottom-5 left-5 bg-white p-4 rounded-xl shadow flex gap-3">
<div class="bg-green-100 w-8 h-8 flex items-center justify-center rounded-full">✔</div>
<div>
<p class="text-sm text-gray-500">Open Daily</p>
<p class="font-semibold">6AM - 6PM</p>
</div>
</div>
</div>

</section>

<!-- ABOUT -->

<section id="about" class="section container grid md:grid-cols-2 gap-16 items-center">

<div class="bg-gradient-to-br from-blue-600 to-indigo-600 h-[350px] rounded-3xl flex items-center justify-center text-white text-5xl">
🧰
</div>

<div>
<h2 class="text-4xl font-bold">
Your Trusted Motorcycle Partner Since 2009
</h2>

<p class="text-gray-600 mt-4">
Serving Bulacan with trusted parts and expert service.
</p>

<div class="grid grid-cols-2 gap-6 mt-6 text-sm">
<div class="flex gap-2"><i data-lucide="check-circle"></i>Quality Parts</div>
<div class="flex gap-2"><i data-lucide="users"></i>Expert Team</div>
<div class="flex gap-2"><i data-lucide="zap"></i>Fast Service</div>
<div class="flex gap-2"><i data-lucide="dollar-sign"></i>Fair Pricing</div>
</div>

</div>

</section>

<!-- FEATURES -->

<section class="section container text-center">

<h2 class="text-4xl font-bold">Everything You Need</h2>

<div class="grid md:grid-cols-4 gap-6 mt-12">

<div class="card p-6">
<i data-lucide="box" class="text-blue-600 mb-3"></i>
<p>Parts</p>
</div>

<div class="card p-6">
<i data-lucide="search" class="text-blue-600 mb-3"></i>
<p>Compatibility</p>
</div>

<div class="card p-6">
<i data-lucide="wrench" class="text-blue-600 mb-3"></i>
<p>Repairs</p>
</div>

<div class="card p-6">
<i data-lucide="clipboard-check" class="text-blue-600 mb-3"></i>
<p>Pre-order</p>
</div>

</div>

</section>

<!-- COMPATIBILITY -->

<section id="compat" class="section container">

<h2 class="text-4xl font-bold text-center">Find the Right Part</h2>

<div class="card p-8 mt-10 max-w-xl mx-auto">

<form method="GET" action="/compatibility">

<input name="make" class="border p-3 w-full mb-3 rounded-lg" placeholder="Motorcycle Make">
<input name="model" class="border p-3 w-full mb-3 rounded-lg" placeholder="Model">
<input name="part" class="border p-3 w-full mb-3 rounded-lg" placeholder="Part">

<button type="submit" class="btn btn-primary w-full">Check Compatibility</button>

</form>

</div>

</section>

<!-- PREORDER -->

<!-- PREORDER -->

<section id="preorder" class="section container grid md:grid-cols-2 gap-12 items-start">

<!-- LEFT: FORM -->

<div class="card p-8">
<h3 class="text-xl font-semibold mb-6">Pre-Order Form</h3>

<input class="border p-3 w-full mb-4 rounded-lg" placeholder="Customer Name">
<input class="border p-3 w-full mb-4 rounded-lg" placeholder="Contact Number">

<div class="grid grid-cols-2 gap-4 mb-4">
<select class="border p-3 rounded-lg">
<option>Select Make</option>
</select>

<select class="border p-3 rounded-lg">
<option>Select Model</option>
</select>
</div>

<form method="GET" action="/preorder-parts">

<input class="border p-3 w-full mb-4 rounded-lg" placeholder="Part Name">
<input class="border p-3 w-full mb-4 rounded-lg" placeholder="Quantity">

<input class="border p-3 w-full mb-4 rounded-lg" placeholder="Down Payment (₱)">

<textarea class="border p-3 w-full mb-6 rounded-lg" placeholder="Notes (optional)"></textarea>

<div class="flex gap-3">
<button class="btn btn-primary w-full">Submit Pre-Order</button>
<button class="btn border w-24">Reset</button>
</div>
</form>
</div>

<!-- RIGHT SIDE -->

<div class="space-y-6">

<!-- HOW IT WORKS -->

<div class="card p-6">
<h3 class="text-lg font-semibold mb-6">How It Works</h3>

<div class="space-y-5 text-sm">

<div class="flex gap-4">
<div class="bg-blue-600 text-white w-10 h-10 flex items-center justify-center rounded-full">
<i data-lucide="box"></i>
</div>
<div>
<p class="font-medium">1. Submit Pre-Order</p>
<p class="text-gray-500">Fill out the form with your part details</p>
</div>
</div>

<div class="flex gap-4">
<div class="bg-blue-600 text-white w-10 h-10 flex items-center justify-center rounded-full">
<i data-lucide="credit-card"></i>
</div>
<div>
<p class="font-medium">2. Pay 50% Down Payment</p>
<p class="text-gray-500">We’ll send GCash details via SMS</p>
</div>
</div>

<div class="flex gap-4">
<div class="bg-blue-600 text-white w-10 h-10 flex items-center justify-center rounded-full">
<i data-lucide="clock"></i>
</div>
<div>
<p class="font-medium">3. We Process Your Order</p>
<p class="text-gray-500">Your part will be ordered and reserved</p>
</div>
</div>

<div class="flex gap-4">
<div class="bg-blue-600 text-white w-10 h-10 flex items-center justify-center rounded-full">
<i data-lucide="smartphone"></i>
</div>
<div>
<p class="font-medium">4. Pick Up & Pay Balance</p>
<p class="text-gray-500">Pay remaining 50% on pickup</p>
</div>
</div>

</div>
</div>

<!-- PAYMENT INFO -->

<div class="p-6 rounded-2xl text-white shadow-xl"
style="background: linear-gradient(135deg,#4f46e5,#9333ea,#ec4899);">

<h3 class="text-lg font-semibold mb-5">Payment & Pickup Information</h3>

<ul class="space-y-3 text-sm">

<li class="flex gap-2 items-start">
✔ <span><b>Pre-Order:</b> 50% down payment required via GCash</span>
</li>

<li class="flex gap-2 items-start">
✔ <span><b>Available Products:</b> 100% payment required for checkout</span>
</li>

<li class="flex gap-2 items-start">
✔ <span><b>Payment Method:</b> GCash only (details sent via SMS)</span>
</li>

<li class="flex gap-2 items-start">
✔ <span><b>Delivery:</b> Pick up only at our shop in Pandi, Bulacan</span>
</li>

<li class="flex gap-2 items-start">
✔ <span><b>Shop Hours:</b> Open daily from 6:00 AM to 6:00 PM</span>
</li>

</ul>

<div class="mt-6 bg-white/20 p-4 rounded-xl text-xs">
<strong>Note:</strong> After submitting your pre-order, we will contact you within 24 hours to confirm availability and provide GCash payment instructions.
</div>

</div>

</div>

</section>


<!-- CONTACT -->

<section id="contact" class="section container grid md:grid-cols-2 gap-10">

<div class="space-y-4">

<div class="card p-5 flex gap-3 items-center">
<i data-lucide="map-pin" class="text-blue-600"></i>
<div>
<p class="font-semibold">Location</p>
<p class="text-sm text-gray-500">Pandi, Bulacan</p>
</div>
</div>

<div class="card p-5 flex gap-3 items-center">
<i data-lucide="phone" class="text-blue-600"></i>
<div>
<p class="font-semibold">Phone</p>
<p class="text-sm text-gray-500">+63 XXX XXXX XXX</p>
</div>
</div>

<div class="card p-5 flex gap-3 items-center">
<i data-lucide="mail" class="text-blue-600"></i>
<div>
<p class="font-semibold">Email</p>
<p class="text-sm text-gray-500">contact@email.com</p>
</div>
</div>

</div>

<div class="card flex items-center justify-center h-[300px]">
@if(env('GOOGLE_MAPS_API_KEY'))
    <iframe
        width="100%"
        height="100%"
        style="border:0; border-radius: 24px;"
        loading="lazy"
        allowfullscreen
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps/embed/v1/place?key={{ env('GOOGLE_MAPS_API_KEY') }}&q=Edmar+Motorcycle+Parts,+Pandi,+Bulacan,+Philippines">
    </iframe>
@else
    <div class="flex flex-col items-center justify-center text-center p-6 text-gray-500">
        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <p>Map will appear here once the API key is added.</p>
    </div>
@endif
</div>

</section>

<!-- FOOTER -->
<footer class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-700 text-white pt-16 pb-8">

<div class="container mx-auto px-6 grid md:grid-cols-3 gap-10 text-left">

<!-- BRAND -->
<div>
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 flex items-center justify-center bg-white/20 rounded-lg font-bold">
            M
        </div>
        <div>
            <h2 class="font-semibold">MOTOTRACK</h2>
            <p class="text-sm opacity-80">Edmar Motorcycle Parts</p>
        </div>
    </div>

    <p class="text-sm opacity-80 leading-relaxed">
        Your trusted partner for quality motorcycle parts and expert repair services in Pandi, Bulacan since 2009.
    </p>

    <p class="mt-4 text-sm opacity-80">
        Open Every Day: 6:00 AM - 6:00 PM
    </p>
</div>

<!-- QUICK LINKS -->
<div>
    <h3 class="font-semibold mb-4">Quick Links</h3>
    <ul class="space-y-2 text-sm opacity-90">
        <li><a href="#" class="hover:underline">Home</a></li>
        <li><a href="#about" class="hover:underline">About</a></li>
        <li><a href="#compat" class="hover:underline">Compatibility</a></li>
        <li><a href="#preorder" class="hover:underline">Pre-Order</a></li>
    </ul>
</div>

<!-- CONTACT -->
<div>
    <h3 class="font-semibold mb-4">Contact Us</h3>
    <ul class="space-y-2 text-sm opacity-90">
        <li>Pandi, Bulacan</li>
        <li>+63 XXX XXX XXXX</li>
        <li>contact@edmarmoto.ph</li>
    </ul>
</div>


</div>

<!-- BOTTOM BAR -->

<div class="border-t border-white/20 mt-10 pt-6 px-6 flex flex-col md:flex-row justify-between items-center text-sm opacity-80">


<p>© 2026 Edmar Motorcycle Parts & Accessories. All rights reserved.</p>

<p class="mt-2 md:mt-0">Powered by MOTOTRACK</p>


</div>

</footer>



<script>
lucide.createIcons();
</script>

</body>
</html>
