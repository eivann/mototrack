<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-8 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">
            
            <!-- Horizontal Card Container -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 dark:border-gray-800 relative overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- Left Info Column -->
                    <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
                        <div>
                            <div class="w-14 h-14 bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-2xl mb-4 flex items-center justify-center shadow-md shadow-indigo-500/30">
                                <i data-lucide="clipboard-list" class="w-7 h-7 text-white"></i>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Custom Pre-Order</h2>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-medium leading-relaxed">Need a specialized or hard-to-find part? Submit a request and our team will source it directly for you.</p>
                        </div>

                        <!-- Payment Details Info -->
                        <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2 text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase tracking-wider">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                                Payment Details
                            </div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 leading-relaxed">
                                Because this is a custom request, the price is not yet determined. Our team will verify availability and price. Payment is only required upon pickup at the shop counter.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest pt-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                            Secure Request Portal
                        </div>
                    </div>

                    <!-- Right Form Column -->
                    <div class="lg:col-span-7">
                        @if ($errors->any())
                            <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-2xl text-red-700 dark:text-red-300 text-xs font-bold space-y-1 mb-4">
                                <div class="flex items-center gap-2 font-black uppercase tracking-wider text-[11px] text-red-800 dark:text-red-200">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
                                    Please correct the following errors:
                                </div>
                                <ul class="list-disc list-inside space-y-0.5 ml-1 pt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="/preorder/store" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                            @csrf

                            <!-- Part Name -->
                            <div>
                                <label for="custom-part-name" class="block text-[11px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <i data-lucide="package" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    Part Name / Description <span class="text-red-500">*</span>
                                </label>
                                <input id="custom-part-name" type="text" name="part_name" value="{{ old('part_name') }}" placeholder="e.g. Brembo RCS 19 Corsa Corta (Part Brand & Name)" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-gray-400" required>
                                <p class="text-[10px] text-gray-400 mt-1">Specify the requested part and parts manufacturer/brand (e.g., Brembo, RCB, Uma Racing, Akrapovic).</p>
                            </div>

                            <!-- Model & Quantity -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="custom-model" class="block text-[11px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                        <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500"></i>
                                        Motorcycle Make &amp; Model <span class="text-red-500">*</span>
                                    </label>
                                    <input id="custom-model" type="text" name="model" value="{{ old('model') }}" placeholder="e.g. Yamaha R6 2018 or Honda Click 125i (or 'Universal')" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-gray-400" required>
                                    <p class="text-[10px] text-gray-400 mt-1">Target motorcycle (Make, Model, Year). Any model is accepted. Enter "Universal" if not bike-specific.</p>
                                </div>
                                <div>
                                    <label for="custom-quantity" class="block text-[11px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                        <i data-lucide="hash" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input id="custom-quantity" type="number" name="quantity" min="1" max="100" value="{{ old('quantity', 1) }}" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-center" required>
                                </div>
                            </div>

                            <!-- reCAPTCHA Container -->
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 flex justify-center shadow-inner">
                                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                            </div>

                            <!-- Honeypot -->
                            <div class="hidden">
                                <input type="text" name="my_extra_field" value="">
                            </div>

                            <!-- Submit -->
                            <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-black text-xs uppercase tracking-widest py-3.5 rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                                <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <i x-show="!loading" data-lucide="send" class="w-4 h-4"></i>
                                Submit Pre-Order Request
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>