<x-app-layout>
    <!-- Background styling with subtle gradient for premium feel -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-200 dark:from-gray-900 dark:to-gray-800 py-16 px-4 sm:px-6 lg:px-8 font-sans">

        <!-- Toast Notification Container -->
        <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

        <div class="max-w-3xl mx-auto mt-10">
            <!-- Header -->
            <div class="text-center mb-10">
                <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight sm:text-5xl">
                    Compatibility Checker
                </h2>
                <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
                    Find the perfect parts for your motorcycle. Search by model, part name, or category.
                </p>
            </div>

            <!-- Search Area -->
            <form id="compatForm" class="relative group z-20">
                <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-[0_8px_30px_rgba(82,68,225,0.15)] dark:shadow-none p-3 md:p-4 border border-gray-50 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row items-end gap-3 md:gap-4">
                        <div class="flex-1 w-full">
                            <label for="makeInput" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider ml-4 mb-1.5">Make</label>
                            <input type="text" id="makeInput" value="{{ request('make') }}" placeholder="E.g., Honda" list="makeSuggestions" autocomplete="off" class="w-full bg-white dark:bg-gray-900 border-2 border-red-400/80 dark:border-gray-600 text-gray-900 dark:text-white rounded-2xl px-5 py-3 focus:outline-none focus:border-indigo-500 focus:ring-0 font-bold placeholder-gray-400 transition-colors">
                            <datalist id="makeSuggestions"></datalist>
                        </div>
                        <div class="flex-1 w-full">
                            <label for="modelInput" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider ml-4 mb-1.5">Model</label>
                            <input type="text" id="modelInput" value="{{ request('model') }}" placeholder="E.g., TMX 125" list="modelSuggestions" autocomplete="off" class="w-full bg-white dark:bg-gray-900 border-2 border-red-400/80 dark:border-gray-600 text-gray-900 dark:text-white rounded-2xl px-5 py-3 focus:outline-none focus:border-indigo-500 focus:ring-0 font-bold placeholder-gray-400 transition-colors">
                            <datalist id="modelSuggestions"></datalist>
                        </div>
                        <div class="flex-1 w-full relative">
                            <label for="yearInput" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider ml-4 mb-1.5">Year/Part</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="yearInput" value="{{ request('part') }}" placeholder="E.g., 2024 or Brake" list="partSuggestions" autocomplete="off" class="w-full bg-white dark:bg-gray-900 border-2 border-red-400/80 dark:border-gray-600 text-gray-900 dark:text-white rounded-2xl px-5 py-3 focus:outline-none focus:border-indigo-500 focus:ring-0 font-bold placeholder-gray-400 transition-colors">
                                <datalist id="partSuggestions"></datalist>
                                <!-- Loading Spinner -->
                                <div id="loadingSpinner" class="hidden absolute right-4 top-[42px]">
                                    <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-auto mt-2 md:mt-0">
                            <button type="submit" class="w-full md:w-auto bg-[#5244e1] hover:bg-[#4336bf] text-white font-bold rounded-2xl px-8 py-3.5 flex items-center justify-center gap-2 transition-all shadow-md ring-2 ring-indigo-500 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-800">
                                <i data-lucide="search" class="w-5 h-5"></i>
                                Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RESULTS GRID -->
                <div id="resultsBox" class="mt-8 transition-all duration-300 relative z-10">
                    <!-- Results injected here -->
                </div>
            </form>
        </div>

    </div>

    <style>
        .highlight { background-color: #fef08a; padding: 0 2px; border-radius: 2px; font-weight: 600; }
        .toast-enter { animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .toast-leave { animation: fadeOut 0.3s ease-in forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    </style>

    <script>
        const makeInput = document.getElementById('makeInput');
        const modelInput = document.getElementById('modelInput');
        const yearInput = document.getElementById('yearInput');
        const resultsBox = document.getElementById('resultsBox');
        const spinner = document.getElementById('loadingSpinner');
        const toastContainer = document.getElementById('toast-container');
        const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
        let timeout = null;
        const suggestionTimers = {};

        function loadSuggestions(field, value, datalistId) {
            clearTimeout(suggestionTimers[datalistId]);
            const datalist = document.getElementById(datalistId);
            const query = (value || '').trim();

            if (!datalist || query.length < 1) {
                if (datalist) datalist.innerHTML = '';
                return;
            }

            suggestionTimers[datalistId] = setTimeout(() => {
                fetch(`{{ route('inventory.suggestions') }}?field=${encodeURIComponent(field)}&q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(response => response.ok ? response.json() : [])
                    .then(data => {
                        datalist.innerHTML = '';
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.value;
                            datalist.appendChild(option);
                        });
                    })
                    .catch(() => { datalist.innerHTML = ''; });
            }, 180);
        }

        // Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const colors = type === 'success' ? 'bg-gray-900 text-white' : 'bg-red-500 text-white';
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            
            toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg toast-enter ${colors}`;
            toast.innerHTML = `${icon} <span class="font-medium">${message}</span>`;
            
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.replace('toast-enter', 'toast-leave');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Add to Cart Logic
        async function addToCart(id) {
            if (!isAuthenticated) {
                window.location.href = "{{ route('login') }}";
                return;
            }

            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            try {
                const response = await fetch(`/cart/add/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    showToast(data.success || 'Item added to cart successfully!');
                } else {
                    showToast(data.error || 'Failed to add item', 'error');
                }
            } catch (error) {
                console.error('Cart Error:', error);
                showToast('Network error or session expired', 'error');
            }
        }

        // Event Delegation for buttons
        resultsBox.addEventListener('click', (e) => {
            const btn = e.target.closest('.add-to-cart-btn');
            if (btn) {
                e.preventDefault();
                const id = btn.dataset.id;
                addToCart(id);
            }
        });

        // Highlight matching text
        function highlightText(text, query) {
            if (!query || !text) return text;
            const terms = query.trim().split(/\s+/).filter(t => t.length > 0);
            if(terms.length === 0) return text;
            const regex = new RegExp(`(${terms.join('|')})`, 'gi');
            return String(text).replace(regex, '<span class="highlight">$1</span>');
        }

        function triggerSearch(e) {
            if (e && e.type === 'submit') e.preventDefault();
            
            clearTimeout(timeout);
            const query = `${makeInput.value} ${modelInput.value} ${yearInput.value}`.trim();

            if (query.length < 2) {
                resultsBox.innerHTML = '';
                return;
            }

            spinner.classList.remove('hidden');

            timeout = setTimeout(() => {
                fetch(`/search-parts?model=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        spinner.classList.add('hidden');
                        resultsBox.innerHTML = '';

                        if (!Array.isArray(data) || data.length === 0) {
                            resultsBox.innerHTML = `
                                <div class="bg-red-50/80 dark:bg-red-900/10 rounded-[2rem] border border-red-200/60 dark:border-red-900/30 p-8 text-center max-w-3xl mx-auto mt-4">
                                    <p class="font-bold text-[17px] text-[#dc2626] dark:text-red-400 mb-2">No matching parts found</p>
                                    <p class="text-[13px] text-red-400 dark:text-red-500 font-bold tracking-wide">Try adjusting your search terms or check a different motorcycle model.</p>
                                </div>
                            `;
                        } else {
                            const grid = document.createElement('div');
                            grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4';
                            
                            data.forEach(item => {
                                const card = document.createElement('div');
                                card.className = 'bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-lg border border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 transition-all duration-300 flex flex-col group';
                                
                                const stockBadge = item.quantity > 0
                                    ? `<span class="text-[11px] font-medium text-gray-500">${item.quantity} in stock</span>`
                                    : `<span class="text-[11px] font-medium text-red-500">Out of Stock</span>`;

                                let actionBtn = '';
                                if (isAuthenticated) {
                                    actionBtn = item.quantity > 0
                                        ? `<button data-id="${item.id}" class="add-to-cart-btn mt-3 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95 cursor-pointer">Add to Cart</button>`
                                        : `<a href="/preorder/${item.id}" class="mt-3 block text-center w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95">Pre-order</a>`;
                                } else {
                                    actionBtn = `<a href="{{ route('login') }}" class="mt-3 block text-center w-full bg-gray-100 text-gray-600 text-sm font-semibold py-2 rounded-lg hover:bg-gray-200">Login to Order</a>`;
                                }

                                const brandText = item.brand ? `<p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">${item.brand}</p>` : '';
                                const modelBadge = item.model ? `<div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded px-1.5 py-0.5 inline-block"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="truncate max-w-[120px]">${item.model}</span></div>` : '';
                                
                                const imageHtml = item.image_url 
                                    ? `<img src="${item.image_url}" alt="${item.part_name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">`
                                    : `<svg class="w-16 h-16 text-gray-300 dark:text-gray-500 group-hover:scale-110 group-hover:text-indigo-200 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>`;

                                card.innerHTML = `
                                    <div class="aspect-square bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-0 relative overflow-hidden group-hover:bg-gray-100 dark:group-hover:bg-gray-600 transition-colors border-b border-gray-100 dark:border-gray-700">
                                        ${imageHtml}
                                        ${item.quantity <= 0 ? '<div class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider shadow-sm">Sold Out</div>' : ''}
                                    </div>
                                    <div class="p-4 flex-1 flex flex-col">
                                        ${brandText}
                                        <h3 class="text-sm font-medium text-gray-800 dark:text-white line-clamp-2 leading-snug flex-1" title="${item.part_name}">
                                            ${highlightText(item.part_name, query)}
                                        </h3>
                                        ${modelBadge}
                                        <div class="flex items-end justify-between mt-3">
                                            <div class="leading-none">
                                                <span class="text-xs font-semibold text-orange-500 align-top">₱</span><span class="text-lg font-bold text-orange-500">${parseFloat(item.price).toFixed(2)}</span>
                                            </div>
                                            ${stockBadge}
                                        </div>
                                        ${actionBtn}
                                    </div>
                                `;
                                grid.appendChild(card);
                            });
                            resultsBox.appendChild(grid);
                        }
                    }).catch(() => {
                        spinner.classList.add('hidden');
                        resultsBox.innerHTML = `
                            <div class="bg-red-50/80 dark:bg-red-900/10 rounded-[2rem] border border-red-200/60 dark:border-red-900/30 p-8 text-center max-w-3xl mx-auto mt-4">
                                <p class="font-bold text-[17px] text-[#dc2626] dark:text-red-400 mb-2">Error connecting to server</p>
                                <p class="text-[13px] text-red-400 dark:text-red-500 font-bold tracking-wide">Please try again in a moment or type slower to avoid rate limits.</p>
                            </div>
                        `;
                    });
            }, 400);
        }

        const compatForm = document.getElementById('compatForm');
        if (compatForm) {
            compatForm.addEventListener('submit', triggerSearch);
        }

        makeInput.addEventListener('input', () => loadSuggestions('brand', makeInput.value, 'makeSuggestions'));
        modelInput.addEventListener('input', () => loadSuggestions('model', modelInput.value, 'modelSuggestions'));
        yearInput.addEventListener('input', () => loadSuggestions('part_name', yearInput.value, 'partSuggestions'));

        [makeInput, modelInput, yearInput].forEach(el => {
            el.addEventListener('keyup', triggerSearch);
        });

        // Auto-trigger search if query exists
        document.addEventListener('DOMContentLoaded', () => {
            if (makeInput.value || modelInput.value || yearInput.value) {
                triggerSearch();
            }
        });
</script>
</x-app-layout>
