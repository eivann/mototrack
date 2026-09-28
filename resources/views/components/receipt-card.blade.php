@props(['reference', 'date', 'title' => 'MOTOTRACK'])

<div class="bg-white dark:bg-gray-800 w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-gray-100 dark:border-gray-700 transition-colors duration-300" id="printableArea">
    <!-- Receipt Header -->
    <div class="p-8 text-center border-b border-gray-200 dark:border-gray-700 border-dashed">
        <div class="flex justify-center items-center mb-3 text-indigo-600 dark:text-indigo-400">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-wider">{{ $title }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Edmar Motorcycle Parts</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Pandi, Bulacan, Philippines</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Ref #: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $reference }}</span></p>
        <p class="text-xs text-gray-400 dark:text-gray-500">Date: {{ $date }}</p>
    </div>

    <!-- Receipt Body -->
    <div {{ $attributes->merge(['class' => 'p-6']) }}>
        {{ $slot }}
    </div>

    <!-- Footer -->
    <div class="p-8 text-center text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <p class="font-medium text-gray-600 dark:text-gray-400 mb-1">Thank you for your purchase!</p>
        <p>Please keep this receipt for returns/exchange.</p>
        <p class="mt-4 opacity-50 text-[10px] uppercase tracking-widest font-black text-red-500 dark:text-red-400">*** This is not an official receipt ***</p>
        <p class="mt-2 opacity-50 text-[10px] uppercase tracking-widest">Powered by MotoTrack</p>
    </div>
</div>
