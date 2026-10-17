@props([
    'action',
    'lines' => [],      // [['name' => string, 'qty' => int, 'subtotal' => float], ...]
    'total' => 0,
    'reference' => null,
    'label' => 'Confirm pickup & payment',
    'alreadyPaid' => false, // legacy pre-orders paid before this flow: no cash is collected again
])

{{-- One labelled cash action: shows the lines and total due, takes cash tendered,
     shows live change, and posts once. The submit button disables itself on the
     first click so a double-click cannot send two requests; the server is also
     idempotent and row-locked. Cash only: no other payment method is offered. --}}
<div x-data="{
        open: false,
        busy: false,
        total: {{ (float) $total }},
        paid: {{ $alreadyPaid ? 'true' : 'false' }},
        tendered: '',
        get change() { const t = parseFloat(this.tendered); return isNaN(t) ? 0 : Math.max(0, t - this.total); },
        get enough() { return this.paid || (parseFloat(this.tendered) >= this.total && this.total > 0); },
        money(n) { return '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    }" class="inline-block">
    <button type="button" @click="open = true; $nextTick(() => $refs.cash && $refs.cash.focus())"
            class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400">
        <i data-lucide="banknote" class="w-4 h-4"></i> {{ $label }}
    </button>

    <div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/60" role="dialog" aria-modal="true">
        <div @click.outside="open = false" class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 text-left">
            <form method="POST" action="{{ $action }}" @submit="if (!enough || busy) { $event.preventDefault(); return; } busy = true;">
                @csrf
                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ $label }}</h3>
                        @if($reference)
                            <p class="text-[10px] font-mono uppercase text-gray-400 mt-0.5">{{ $reference }}</p>
                        @endif
                    </div>

                    <ul class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @foreach($lines as $line)
                            <li class="py-2 flex justify-between gap-3">
                                <span class="text-gray-800 dark:text-gray-200">{{ $line['name'] }} <span class="text-gray-400">× {{ $line['qty'] }}</span></span>
                                <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($line['subtotal'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex justify-between items-center text-base">
                        <span class="font-bold text-gray-600 dark:text-gray-300">Total due</span>
                        <span class="font-black text-gray-900 dark:text-white">₱{{ number_format((float) $total, 2) }}</span>
                    </div>

                    @if($alreadyPaid)
                        <p class="text-sm rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 p-3">
                            This pre-order was already paid. No additional cash will be collected.
                        </p>
                    @else
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1" for="cash-{{ md5($action) }}">Cash tendered (₱)</label>
                            <input id="cash-{{ md5($action) }}" x-ref="cash" type="number" step="0.01" min="0" name="amount_tendered" x-model="tendered" required
                                   class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-bold text-gray-600 dark:text-gray-300">Change</span>
                            <span class="font-black" :class="enough ? 'text-emerald-600' : 'text-gray-400'" x-text="money(change)"></span>
                        </div>
                        <p x-show="tendered !== '' && !enough" class="text-xs text-red-600">Cash tendered is less than the total due.</p>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl flex justify-end gap-2">
                    <button type="button" @click="open = false" class="px-4 py-2 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Back</button>
                    <button type="submit" :disabled="!enough || busy"
                            class="px-4 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg">
                        <span x-show="!busy">{{ $label }}</span><span x-show="busy" x-cloak>Processing…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
