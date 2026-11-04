{{-- RD2-R6: visible notice next to the confirmation checkbox. Rendered ONLY while RR_AUTO_CANCEL_ENABLED is true. --}}
@if (config('repair_reservations.auto_cancel.enabled'))
    <div data-auto-cancel-notice role="note" class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
        <p class="font-semibold">Automatic cancellation rule</p>
        <p class="mt-1">@include('repair_reservations.partials.auto-cancel-text')</p>
    </div>
@endif
