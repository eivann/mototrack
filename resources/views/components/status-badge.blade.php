@props(['status', 'type' => 'default'])

@php
    $status = strtolower($status);
    $classes = 'px-3 py-1 rounded-full text-xs font-bold text-white w-fit ';
    
    $colorClasses = [
        'pending' => 'bg-amber-500',
        'ongoing' => 'bg-indigo-500',
        'done' => 'bg-emerald-600',
        'completed' => 'bg-emerald-600',
        'cancelled' => 'bg-rose-500',
        'paid' => 'bg-emerald-500',
        'unpaid' => 'bg-rose-500',
        'low' => 'bg-rose-500',
        'ok' => 'bg-emerald-500',
    ];

    $classes .= $colorClasses[$status] ?? 'bg-gray-500';
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ ucfirst($status) }}
</span>
