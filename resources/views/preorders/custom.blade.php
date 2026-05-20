<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6">
        <div class="max-w-xl mx-auto">
            
            <!-- Page Header -->
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-lg shadow-indigo-500/30 rotate-3 hover:rotate-0 transition-all duration-300">
                    <i data-lucide="clipboard-list" class="w-10 h-10 text-white"></i>
                </div>
                <h2 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter mb-3">Custom Pre-Order</h2>
                <p class="text-gray-500 dark:text-gray-400 font-medium max-w-sm mx-auto leading-relaxed">Need a specialized or hard-to-find part? Submit a request and our team will source it directly for you.</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 md:p-10 shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden">
                <!-- Background decorative elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-3xl -z-10 transform translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
                
                <form method="POST" action="/preorder/store" class="space-y-6 relative z-10" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <!-- Part Name -->
                    <div>
                        <label for="custom-part-name" class="block text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                <i data-lucide="package" class="w-3.5 h-3.5"></i>
                            </div>
                            Part Name <span class="text-red-500">*</span>
                        </label>
                        <input id="custom-part-name" type="text" name="part_name" placeholder="e.g. Brembo RCS 19 Master Cylinder" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-gray-400" required>
                    </div>

                    <!-- Model Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="custom-model" class="block text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                </div>
                                Motorcycle Model
                            </label>
                            <input id="custom-model" type="text" name="model" placeholder="e.g. Yamaha R6 2018" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-gray-400">
                        </div>
                        <div>
                            <label for="custom-quantity" class="block text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                                    <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                                </div>
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <input id="custom-quantity" type="number" name="quantity" min="1" value="1" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-center">
                        </div>
                    </div>

                    <!-- Down Payment Info -->
                    <div>
                        <label class="block text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center text-yellow-600">
                                <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                            </div>
                            Required Down Payment (50%)
                        </label>
                        <div class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 flex items-start gap-3">
                            <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                            <p class="text-xs font-bold text-gray-600 dark:text-gray-400 leading-relaxed">
                                Because this is a custom request, the price is not yet determined. A strict <span class="text-blue-600 dark:text-blue-400 font-black">50% down payment</span> is required for all orders. You will receive an invoice via Xendit once our team verifies the total price of your requested part.
                            </p>
                        </div>
                    </div>

                    <!-- reCAPTCHA Container -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl py-6 flex justify-center mt-8 shadow-inner">
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    </div>

                    <!-- Honeypot -->
                    <div class="hidden">
                        <input type="text" name="my_extra_field" value="">
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-black text-sm uppercase tracking-widest py-5 rounded-2xl shadow-xl shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 active:scale-[0.98] transition-all flex items-center justify-center gap-3 mt-8 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                        <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <i x-show="!loading" data-lucide="send" class="w-5 h-5"></i>
                        Submit Pre-Order Request
                    </button>
                </form>
            </div>
            
            <div class="mt-8 text-center flex justify-center items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                Secure Request Portal
            </div>
        </div>
    </div>
</x-app-layout>