<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Owner Dashboard
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Manage Inventory -->
                <a href="/inventory" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all active:scale-[0.98] flex flex-col gap-4">
                    <div class="bg-indigo-50 w-14 h-14 rounded-xl flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-indigo-700 transition-colors">Manage Inventory</h3>
                        <p class="text-sm text-gray-500 mt-1">Add, edit, or remove parts from your catalog.</p>
                    </div>
                </a>

                <!-- POS / Record Sale -->
                <a href="/sales" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all active:scale-[0.98] flex flex-col gap-4">
                    <div class="bg-emerald-50 w-14 h-14 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-emerald-700 transition-colors">Point of Sale (POS)</h3>
                        <p class="text-sm text-gray-500 mt-1">Smart Cart checkout and automated receipts.</p>
                    </div>
                </a>

                <!-- Generate Reports -->
                <a href="/sales/history" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all active:scale-[0.98] flex flex-col gap-4">
                    <div class="bg-blue-50 w-14 h-14 rounded-xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-blue-700 transition-colors">Generate Reports</h3>
                        <p class="text-sm text-gray-500 mt-1">View revenue, transaction history, and metrics.</p>
                    </div>
                </a>

                <!-- Track Repair -->
                <a href="/repairs" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all active:scale-[0.98] flex flex-col gap-4">
                    <div class="bg-purple-50 w-14 h-14 rounded-xl flex items-center justify-center text-purple-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-purple-700 transition-colors">Track Repair</h3>
                        <p class="text-sm text-gray-500 mt-1">Manage service jobs and customer statuses.</p>
                    </div>
                </a>

                <!-- Process Pre-order -->
                <a href="/preorders" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-200 transition-all active:scale-[0.98] flex flex-col gap-4">
                    <div class="bg-cyan-50 w-14 h-14 rounded-xl flex items-center justify-center text-cyan-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-cyan-700 transition-colors">Process Pre-order</h3>
                        <p class="text-sm text-gray-500 mt-1">Handle customer requests for unavailable parts.</p>
                    </div>
                </a>

                <!-- Low Stock Alert -->
                <a href="/low-stock" class="group bg-white p-6 rounded-2xl shadow-sm border border-amber-200 hover:shadow-md hover:border-amber-400 transition-all active:scale-[0.98] flex flex-col gap-4 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-amber-50 rounded-bl-full -z-10 group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="bg-amber-100 w-14 h-14 rounded-xl flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-xl text-gray-900 group-hover:text-amber-700 transition-colors">Low Stock Alerts</h3>
                        <p class="text-sm text-gray-500 mt-1">Monitor items dropping below thresholds.</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>