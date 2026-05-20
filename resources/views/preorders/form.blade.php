<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-10 px-4">

    <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-8">

        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Pre-order Item
        </h2>

        <!-- ITEM INFO -->
        <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700/50">
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $item->part_name }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Model: {{ $item->model ?? 'N/A' }}</p>
            <p class="text-green-600 dark:text-green-400 font-bold mt-1">₱{{ number_format($item->price, 2) }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 text-red-500 dark:text-red-400 text-sm">
                <ul>@foreach ($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <!-- FORM -->
        <form method="POST" action="/preorder/store" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <input type="hidden" name="part_name" value="{{ $item->part_name }}">
            <input type="hidden" name="model" value="{{ $item->model }}">
            <input type="hidden" name="price" value="{{ $item->price }}">

            <!-- Quantity -->
            <div class="mb-4">
                <label for="quantity" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 outline-none transition-all">
            </div>

            <!-- Down Payment (Auto Calculated) -->
            <div class="mb-4">
                <label for="down_payment" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    Required Down Payment (50%)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 uppercase">Auto-calculated</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-bold">₱</span>
                    </div>
                    <input type="number" id="down_payment" name="down_payment" step="0.01" readonly
                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-4 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 font-bold outline-none cursor-not-allowed focus:outline-none">
                </div>
            </div>

            <!-- PAYMENT METHOD SELECTOR -->
            <div class="mb-4">
                <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                    Select Payment Method
                </label>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Option 1: GCash (Xendit) -->
                    <label class="payment-card relative flex flex-col p-4 bg-white dark:bg-gray-800 border-2 border-blue-500 rounded-xl cursor-pointer hover:border-blue-600 transition-all select-none">
                        <input type="radio" name="payment_method" value="gcash" checked class="sr-only" onclick="selectPaymentMethod('gcash', 'PMP')">
                        <span class="flex items-center gap-2 mb-2">
                            <span class="w-4 h-4 rounded-full border border-blue-500 flex items-center justify-center bg-blue-500">
                                <span class="w-2 h-2 rounded-full bg-white"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">GCash</span>
                        </span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Pay down payment via GCash</span>
                        <span class="absolute top-2 right-2 text-[9px] font-extrabold text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded uppercase tracking-wider scale-90">Xendit</span>
                    </label>

                    <!-- Option 2: RCPAY Maya -->
                    <label class="payment-card relative flex flex-col p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-blue-400 transition-all select-none">
                        <input type="radio" name="payment_method" value="rcpay" class="sr-only" onclick="selectPaymentMethod('rcpay', 'PMP')">
                        <span class="flex items-center gap-2 mb-2">
                            <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600 flex items-center justify-center bg-transparent">
                                <span class="w-2 h-2 rounded-full bg-transparent"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Maya</span>
                        </span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Pay instantly using Maya e-Wallet</span>
                        <span class="absolute top-2 right-2 text-[9px] font-extrabold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-1.5 py-0.5 rounded uppercase tracking-wider scale-90">RCPay</span>
                    </label>

                    <!-- Option 3: RCPAY QRPH -->
                    <label class="payment-card relative flex flex-col p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-blue-400 transition-all select-none">
                        <input type="radio" name="payment_method" value="rcpay" class="sr-only" onclick="selectPaymentMethod('rcpay', 'QRPH')">
                        <span class="flex items-center gap-2 mb-2">
                            <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600 flex items-center justify-center bg-transparent">
                                <span class="w-2 h-2 rounded-full bg-transparent"></span>
                            </span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">QRPH</span>
                        </span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">Scan and pay with banking apps</span>
                        <span class="absolute top-2 right-2 text-[9px] font-extrabold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded uppercase tracking-wider scale-90">QRPH</span>
                    </label>
                </div>
            </div>

            <!-- HIDDEN BANK CODE -->
            <input type="hidden" id="rcpay_bank_code" name="bank_code" value="PMP">

            <!-- METHOD EXPLANATION BOX -->
            <div id="payment-explanation" class="mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-5 flex gap-4 transition-all duration-300">
                <div id="explanation-icon" class="bg-blue-600 p-2.5 rounded-xl h-fit text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <h4 id="explanation-title" class="font-black text-blue-900 dark:text-blue-300 uppercase tracking-tight text-xs">Secure GCash Payment</h4>
                    <p id="explanation-desc" class="text-[11px] text-blue-700/70 dark:text-blue-400/70 mt-1 leading-relaxed">You will be securely redirected to Xendit's encrypted gateway to complete your transaction via GCash.</p>
                </div>
            </div>

            <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed">
                <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Submit Pre-order
            </button>
        </form>

        <script>
            function selectPaymentMethod(method, bankCode) {
                    // Update hidden input for bank code
                    const bankCodeInput = document.getElementById('rcpay_bank_code');
                    if (bankCodeInput) {
                        bankCodeInput.value = bankCode;
                    }

                    // Update radio button styles
                    const cards = document.querySelectorAll('.payment-card');
                    cards.forEach((card, index) => {
                        const radio = card.querySelector('input[type="radio"]');
                        const circle = card.querySelector('.rounded-full');
                        const dot = circle.querySelector('.rounded-full');
                        
                        if ((radio.value === method && method === 'gcash' && index === 0) || 
                            (radio.value === method && method === 'rcpay' && ((index === 1 && bankCode === 'PMP') || (index === 2 && bankCode === 'QRPH')))) {
                            card.classList.remove('border-gray-200', 'dark:border-gray-700');
                            card.classList.add('border-blue-500');
                            circle.classList.remove('border-gray-300', 'dark:border-gray-600');
                            circle.classList.add('border-blue-500', 'bg-blue-500');
                            dot.classList.remove('bg-transparent');
                            dot.classList.add('bg-white');
                            radio.checked = true;
                        } else {
                            card.classList.remove('border-blue-500', 'border-blue-600');
                            card.classList.add('border-gray-200', 'dark:border-gray-700');
                            circle.classList.remove('border-blue-500', 'bg-blue-500');
                            circle.classList.add('border-gray-300', 'dark:border-gray-600');
                            dot.classList.remove('bg-white');
                            dot.classList.add('bg-transparent');
                        }
                    });

                    // Update explanation box content
                    const title = document.getElementById('explanation-title');
                    const desc = document.getElementById('explanation-desc');
                    const explanationBox = document.getElementById('payment-explanation');
                    const iconBox = document.getElementById('explanation-icon');

                    if (method === 'gcash') {
                        title.innerText = 'Secure GCash Payment';
                        desc.innerText = 'You will be securely redirected to Xendit\'s encrypted gateway to complete your transaction via GCash.';
                        
                        // Color classes
                        explanationBox.className = "mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-5 flex gap-4 transition-all duration-300";
                        iconBox.className = "bg-blue-600 p-2.5 rounded-xl h-fit text-white";
                    } else if (method === 'rcpay' && bankCode === 'PMP') {
                        title.innerText = 'Maya E-Wallet (via RCPAY)';
                        desc.innerText = 'You will be securely redirected to RCPAY PLUS gateway to complete your down payment instantly using Maya (PayMaya).';

                        // Color classes (Indigo for Maya/RCPAY)
                        explanationBox.className = "mb-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-2xl p-5 flex gap-4 transition-all duration-300";
                        iconBox.className = "bg-indigo-600 p-2.5 rounded-xl h-fit text-white";
                    } else if (method === 'rcpay' && bankCode === 'QRPH') {
                        title.innerText = 'QRPH Universal Scan (via RCPAY)';
                        desc.innerText = 'A dynamic QRPH code will be generated via RCPAY PLUS. Scan with GCash, Maya, or any major banking app to instantly pay.';

                        // Color classes (Amber for QRPH)
                        explanationBox.className = "mb-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-2xl p-5 flex gap-4 transition-all duration-300";
                        iconBox.className = "bg-amber-600 p-2.5 rounded-xl h-fit text-white";
                    }
                }
            </script>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const quantityInput = document.getElementById('quantity');
                    const downPaymentInput = document.getElementById('down_payment');
                    const itemPrice = {{ $item->price }};

                    function calculateDownPayment() {
                        const qty = parseInt(quantityInput.value) || 0;
                        const total = qty * itemPrice;
                        const downPayment = total * 0.50;
                        downPaymentInput.value = downPayment.toFixed(2);
                    }

                    // Calculate initially
                    calculateDownPayment();

                    // Recalculate on input change
                    quantityInput.addEventListener('input', calculateDownPayment);
                });
            </script>

            <!-- Google reCAPTCHA -->
            <div class="mb-4 flex flex-col items-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>

            <!-- Honeypot -->
            <div class="hidden"><input type="text" name="my_extra_field" value=""></div>

            @if(session('success'))
                <div class="mb-4 text-green-600 dark:text-green-400">{{ session('success') }}</div>
            @endif

            <!-- Buttons -->
            <div class="flex justify-between mt-6">
                <a href="/preorder-parts" class="text-gray-600 dark:text-gray-400 hover:underline">← Back</a>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors shadow-sm">
                    Submit Pre-order
                </button>
            </div>
        </form>

    </div>

</div>
</x-app-layout>