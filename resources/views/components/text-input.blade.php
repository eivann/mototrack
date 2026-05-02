@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 transition-shadow w-full p-3 shadow-sm']) }}>
