<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-16">
        <!-- Hero Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl mb-6">
                    <i data-lucide="help-circle" class="w-8 h-8 text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tighter mb-3">Frequently Asked Questions</h1>
                <p class="text-gray-500 dark:text-gray-400 font-medium max-w-xl mx-auto text-sm sm:text-base leading-relaxed">
                    Everything you need to know about ordering, payments, pre-orders, and more at Edmar Motorcycle Parts and Accessories.
                </p>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- FAQ Accordion -->
            <div x-data="{ open: null }" class="space-y-4">

                <!-- Q1 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 1 ? null : 1" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 1">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">Is the product I see on the website currently in stock?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 1 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 1" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>The catalog reflects real-time inventory, so if a product is listed as available, it is in stock at the shop and ready for regular order.</p>
                            <p>If an item is out of stock, it will be marked as such.</p>
                        </div>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 2 ? null : 2" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 2">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-black">2</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">How do I know if a part is compatible with my motorcycle?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 2 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 2" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>Use the compatibility checker by entering your motorcycle's brand, model, and year before ordering.</p>
                            <p>The checker only covers products and brands currently in Edmar Motorcycle Parts and Accessories' inventory, not a general database of all parts worldwide.</p>
                        </div>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 3 ? null : 3" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 3">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xs font-black">3</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">What is a pre-order and when do I use it?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 3 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 3" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>Pre-order is used when a product is currently out of stock, so you reserve it ahead of restock instead of missing out.</p>
                            <p>Select the product, click Pre-Order, then pay a 50% downpayment through GCash to confirm your reservation.</p>
                        </div>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 4 ? null : 4" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 4">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-black">4</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">When do I pay the remaining balance for a pre-order?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 4 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 4" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>The remaining balance is paid upon pickup, once the item has arrived and is ready at the shop.</p>
                            <p>You will be notified through your account once your pre-ordered item is ready for pickup.</p>
                        </div>
                    </div>
                </div>

                <!-- Q5 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 5 ? null : 5" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 5">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-black">5</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">What payment method is accepted?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 5 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 5" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>Payment is made through GCash for both downpayment and full payment.</p>
                        </div>
                    </div>
                </div>

                <!-- Q6 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 6 ? null : 6" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 6">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xs font-black">6</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">Is a printed receipt provided after purchase?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 6 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 6" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>No, the shop does not issue a printed receipt. A digital receipt is generated and saved under your Order History instead.</p>
                        </div>
                    </div>
                </div>

                <!-- Q7 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 7 ? null : 7" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 7">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs font-black">7</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">How do I check the status of my order?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 7 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 7" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>Go to your account to see the current status, such as Pending, Downpayment Received, Ready for Pickup, or Completed.</p>
                        </div>
                    </div>
                </div>

                <!-- Q8 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 8 ? null : 8" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 8">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 flex items-center justify-center text-xs font-black">8</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">Can I cancel my order?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 8 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 8" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>Yes, even if it is fully paid or has a downpayment, as long as you have not picked it up from the shop yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Q9 -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden transition-all">
                    <button @click="open = open === 9 ? null : 9" class="w-full text-left px-6 sm:px-8 py-5 sm:py-6 flex items-center justify-between gap-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset group" :aria-expanded="open === 9">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="shrink-0 w-8 h-8 rounded-xl bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 flex items-center justify-center text-xs font-black">9</span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">Why do I need to read the Terms and Conditions before paying?</h3>
                        </div>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300" :class="open === 9 ? 'rotate-180 text-indigo-600' : ''"></i>
                    </button>
                    <div x-show="open === 9" x-collapse x-cloak class="px-6 sm:px-8 pb-6 sm:pb-8">
                        <div class="pl-12 text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                            <p>It explains the payment, refund, cancellation, and compatibility checker rules before your order is confirmed, since these directly affect your purchase.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Still need help? -->
            <div class="mt-12 bg-indigo-600 rounded-3xl p-8 sm:p-10 text-white text-center relative overflow-hidden shadow-xl shadow-indigo-200 dark:shadow-none">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative z-10">
                    <h3 class="text-2xl font-black tracking-tighter mb-3">Still have questions?</h3>
                    <p class="text-indigo-100 font-medium mb-6 text-sm max-w-md mx-auto">Can't find what you're looking for? Visit our shop or reach out through our contact section.</p>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <a href="/#contact" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-sm hover:scale-105 transition-all shadow-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Contact Us</a>
                        <a href="/browse" class="px-8 py-4 bg-indigo-500/50 text-white border border-white/20 rounded-2xl font-black text-sm hover:bg-indigo-400 transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">Browse Parts</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
