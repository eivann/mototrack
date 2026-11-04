@props([
    'item' => null,       // InventoryItem (uses its image_url when an image_path is set)
    'src' => null,        // or an explicit URL
    'alt' => null,
    'fit' => 'contain',   // 'contain' shows the whole part undistorted; 'cover' fills the box
    'imgClass' => '',
    'sizes' => '100vw', // rendered width hint for srcset pictures: tell the browser how wide the box is
    'eager' => false,   // true for pictures that are visible without scrolling (not lazy-loaded)
])

{{-- One product picture convention for every listing: lazy, async-decoded, never stretched, and a
     neutral "package" placeholder when there is no picture or the file cannot be loaded. Uploaded
     files are only referenced, never changed. --}}
@php
    $url = $src ?? (($item && !empty($item->image_path)) ? $item->image_url : null);
    $label = $alt ?? ($item?->part_name ?: 'Part image');
    // Pictures uploaded through ProductImageService have 320/640/full widths; legacy ones just have a src.
    $srcset = ($src === null && $item && !empty($item->image_path)) ? $item->image_srcset : null;
@endphp
<div {{ $attributes->merge(['class' => 'relative overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-900/50']) }}>
    <div class="absolute inset-0 flex items-center justify-center text-gray-300 dark:text-gray-600 {{ $url ? 'hidden' : '' }}" data-image-fallback aria-hidden="true">
        <svg class="w-1/3 h-1/3 max-w-[4rem] max-h-[4rem]" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </div>
    @if($url)
        <img src="{{ $url }}" @if($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif alt="{{ $label }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
             class="relative w-full h-full {{ $fit === 'cover' ? 'object-cover' : 'object-contain' }} {{ $imgClass }}"
             onerror="this.onerror=null;this.classList.add('hidden');this.parentNode.querySelector('[data-image-fallback]').classList.remove('hidden');">
    @endif
    @if($url === null)
        <span class="sr-only">{{ $label }}: no picture available</span>
    @endif
</div>
