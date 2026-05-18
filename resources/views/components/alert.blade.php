@props(['type' => 'success', 'message'])

@php
    $classes = $type === 'success' 
        ? 'bg-green-100 dark:bg-green-900/30 border-green-400 dark:border-green-700 text-green-700 dark:text-green-300' 
        : 'bg-red-100 dark:bg-red-900/30 border-red-400 dark:border-red-700 text-red-700 dark:text-red-300';
@endphp

<div {{ $attributes->merge(['class' => "mb-4 border px-4 py-3 rounded relative $classes"]) }}>
    <span class="block sm:inline">{{ $message }}</span>
</div>
