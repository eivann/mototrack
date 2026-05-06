<x-app-layout>
    <!-- Background styling with subtle gradient for premium feel -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-200 py-16 px-4 sm:px-6 lg:px-8 font-sans">

        <!-- Toast Notification Container -->
        <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

        <div class="max-w-3xl mx-auto mt-10">
            <!-- Header -->
            <div class="text-center mb-10">
                <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">
                    Compatibility Checker
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    Find the perfect parts for your motorcycle. Search by model, part name, or category.
                </p>
            </div>

            <!-- Search Area -->
            <div class="relative group">
                <!-- Decorative background glow -->
                <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-500"></div>
                
                <div class="relative bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl flex items-center p-2 border border-white/50">
                    <div class="pl-4 pr-2 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="searchInput" autocomplete="off" placeholder="E.g., Honda Brake Pad..." value="{{ $searchQuery ?? '' }}"
                        class="w-full bg-transparent border-none text-gray-800 text-lg focus:ring-0 placeholder-gray-400 py-3 px-2 outline-none">
                    
                    <!-- Loading Spinner (Hidden by default) -->
                    <div id="loadingSpinner" class="pr-4 hidden">
                        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- RESULTS GRID -->
                <div id="resultsBox" class="mt-8 transition-all duration-300 relative z-10">
                    <!-- Results injected here -->
                </div>
            </div>
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
        const input = document.getElementById('searchInput');
        const resultsBox = document.getElementById('resultsBox');
        const spinner = document.getElementById('loadingSpinner');
        const toastContainer = document.getElementById('toast-container');
        let timeout = null;
        let currentQuery = '';

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
                    // Optional: update a cart counter if you have one
                } else {
                    showToast(data.error || 'Failed to add item', 'error');
                }
            } catch (error) {
                console.error('Cart Error:', error);
                showToast('Network error or session expired', 'error');
            }
        }

        // Event Delegation for Add to Cart buttons
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
            const terms = query.trim().split(/\\s+/).filter(t => t.length > 0);
            if(terms.length === 0) return text;
            const regex = new RegExp(`(${terms.join('|')})`, 'gi');
            return String(text).replace(regex, '<span class="highlight">$1</span>');
        }

        input.addEventListener('keyup', function (e) {
            clearTimeout(timeout);
            let query = this.value.trim();

            if (query.length < 2) {
                resultsBox.innerHTML = '';
                return;
            }

            if (query === currentQuery && resultsBox.innerHTML !== '') {
                return;
            }

            currentQuery = query;
            spinner.classList.remove('hidden');

            timeout = setTimeout(() => {
                fetch(`/search-parts?model=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        spinner.classList.add('hidden');
                        resultsBox.innerHTML = '';

                        if (data.length === 0) {
                            resultsBox.innerHTML = `
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500 max-w-2xl mx-auto">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="font-bold text-xl text-gray-800 mb-2">No matching parts found</p>
                                    <p class="text-gray-500">We couldn't find any parts matching "${query}".</p>
                                </div>
                            `;
                        } else {
                            const grid = document.createElement('div');
                            grid.className = 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4';
                            
                            data.forEach(item => {
                                const card = document.createElement('div');
                                card.className = 'bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg border border-gray-100 hover:border-indigo-300 transition-all duration-300 flex flex-col group';
                                
                                const stockBadge = item.quantity > 0
                                    ? `<span class="text-[11px] font-medium text-gray-500">${item.quantity} items left</span>`
                                    : `<span class="text-[11px] font-medium text-red-500">Out of Stock</span>`;

                                const actionBtn = item.quantity > 0
                                    ? `<button data-id="${item.id}" class="add-to-cart-btn mt-3 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95 cursor-pointer">Add to Cart</button>`
                                    : `<a href="/preorder/${item.id}" class="mt-3 block text-center w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-lg shadow-sm transition-colors active:scale-95">Pre-order</a>`;

                                const brandText = item.brand ? `<p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">${item.brand}</p>` : '';
                                const modelBadge = item.model ? `<div class="mt-1 flex items-center gap-1 text-xs text-gray-500 bg-gray-50 rounded px-1.5 py-0.5 inline-block"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="truncate max-w-[120px]">${item.model}</span></div>` : '';

                                card.innerHTML = `
                                    <div class="aspect-square bg-gray-50 flex items-center justify-center p-4 relative overflow-hidden group-hover:bg-gray-100 transition-colors border-b border-gray-100">
                                        <svg class="w-16 h-16 text-gray-300 group-hover:scale-110 group-hover:text-indigo-200 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        ${item.quantity <= 0 ? '<div class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider shadow-sm">Sold Out</div>' : ''}
                                    </div>
                                    <div class="p-4 flex-1 flex flex-col">
                                        ${brandText}
                                        <h3 class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug flex-1" title="${item.part_name}">
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
                    });
            }, 400);
        });

        // Auto-trigger search if query exists from landing page
        document.addEventListener('DOMContentLoaded', () => {
            if (input.value.trim().length >= 2) {
                input.dispatchEvent(new Event('focus'));
                input.dispatchEvent(new Event('keyup'));
            }
        });
    </script>
</x-app-layout>