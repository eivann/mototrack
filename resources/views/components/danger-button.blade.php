<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center bg-red-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-md hover:bg-red-700 hover:shadow-lg transition-all active:scale-[0.98]']) }}>
    {{ $slot }}
</button>
