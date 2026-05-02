<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center bg-white border border-gray-200 text-gray-700 px-6 py-2.5 rounded-xl font-bold shadow-sm hover:bg-gray-50 transition-colors active:scale-[0.98]']) }}>
    {{ $slot }}
</button>
