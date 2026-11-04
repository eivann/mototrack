<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold mb-1 text-gray-900 dark:text-white">Service Requests</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Customer same-day repair/service requests. A request is <strong>not an appointment</strong> until you accept it. Capacity setting: {{ $capacity }} accepted ({{ $unit }}) — provisional, set in configuration.</p>

        @if (session('success'))<div class="mb-3 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-900">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="mb-3 text-red-600 text-sm"><ul>@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>@endif

        <div class="mb-3"><a href="{{ route('owner.repair-blackouts.index') }}" class="inline-block px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200">Blackouts (closed times)</a></div>

        <div class="flex flex-wrap gap-2 mb-4 text-sm">
            @foreach (['pending' => 'Pending ('.$counts['pending'].')', 'accepted' => 'Accepted ('.$counts['accepted'].' today)', 'history' => 'History'] as $k => $label)
                <a href="{{ route('owner.repair-reservations.index', ['tab' => $k]) }}"
                   class="px-3 py-1.5 rounded-full border {{ $tab === $k ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($items as $item)
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-4 shadow-sm" id="rr-{{ $item->id }}">
                    <div class="flex flex-wrap justify-between gap-2">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $item->reference }} &middot; {{ $item->serviceLabel() }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->customer?->name ?? 'Customer' }} &middot; {{ $item->bike_model }} &middot; {{ $item->stageLabel() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Requested time</div>
                            <div class="text-lg font-black text-gray-900 dark:text-white">{{ $item->dateLabel() }}, {{ $item->timeLabel() }}</div>
                            @if ($item->status === 'pending' && $item->scheduled_at->lte($now))<div class="text-xs font-bold text-red-600 dark:text-red-400">Time has passed</div>@endif
                        </div>
                    </div>
                    <p class="text-sm mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $item->description }}</p>
                    @if ($item->decision_reason)<p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Reason: {{ $item->decision_reason }}</p>@endif

                    @php $blackout = $blackoutConflicts[$item->id] ?? null; @endphp
                    @if ($blackout)
                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-3 text-sm font-semibold text-red-800 dark:text-red-200">
                            Blackout conflict: {{ $blackout->label() }}@if ($blackout->reason) &mdash; {{ $blackout->reason }}@endif.
                            @if ($item->status === 'pending')This request cannot be accepted. Reject it with a reason (for example "Shop closed at that time").
                            @else This appointment stays accepted; nothing was cancelled. Check-in and the repair can still proceed. Cancel it only if you decide to, and tell the customer.@endif
                        </div>
                    @endif
                    @if ($item->status === 'pending')
                        <div class="mt-3 flex flex-wrap gap-2 items-start">
                            @if ($blackout)
                                <button type="button" disabled class="px-4 py-2 rounded-lg bg-gray-300 text-gray-600 text-sm font-bold cursor-not-allowed">Accept (blacked out)</button>
                            @else
                            <form method="POST" action="{{ route('owner.repair-reservations.accept', $item) }}">@csrf
                                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-bold">Accept</button></form>
                            @endif
                            <form method="POST" action="{{ route('owner.repair-reservations.reject', $item) }}" class="flex gap-2">@csrf
                                <input name="reason" required maxlength="255" placeholder="Reason for rejecting" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                                <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold">Reject</button></form>
                        </div>
                    @elseif ($item->status === 'accepted')
                        @php $repair = $item->repair; @endphp
                        @if (! empty($conflicts[$item->id]))
                            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-3 text-sm font-semibold text-red-800 dark:text-red-200">Capacity conflict: repairs ahead of this appointment are overrunning or overlapping, so the shop may be over its limit at this time. Nothing was cancelled &mdash; check the Repairs list and contact the customer if needed.</div>
                        @endif
                        @if ($repair)
                            <div class="mt-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-3 text-sm text-indigo-900 dark:text-indigo-200">
                                <strong>Repair in progress</strong> &middot; est. {{ $repair->estimated_minutes }} min
                                @if ($repair->isOverdue())<span class="font-bold text-red-600 dark:text-red-400"> &middot; overdue</span>@endif
                                &middot; <a href="{{ route('repairs.index') }}#repair-{{ $repair->id }}" class="underline">Manage in Repairs (extend / mark done)</a>
                            </div>
                        @elseif ($item->checked_in_at)
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="text-sm text-green-700 dark:text-green-300 font-semibold">Checked in {{ $item->checked_in_at->timezone(config('repair_reservations.timezone'))->format('g:i:s A') }}{{ $item->received_by ? ' · received by '.$item->received_by : '' }}</span>
                                <form method="POST" action="{{ route('owner.repair-reservations.start-repair', $item) }}" class="flex gap-2 items-center">@csrf
                                    <label class="sr-only" for="est-{{ $item->id }}">Estimated duration</label>
                                    <select id="est-{{ $item->id }}" name="estimated_minutes" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                                        @foreach ([30 => '30 min', 60 => '1 hr', 90 => '1.5 hr', 120 => '2 hr', 180 => '3 hr', 240 => '4 hr'] as $m => $l)
                                            <option value="{{ $m }}" {{ $m === (int) config('repair_reservations.appointment_minutes') ? 'selected' : '' }}>Est. {{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-bold">Start repair</button></form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('owner.repair-reservations.check-in', $item) }}" class="mt-3 flex flex-wrap gap-2 items-center">@csrf
                                <input name="received_by" maxlength="80" placeholder="Received by (optional)" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-bold">Record check-in</button>
                                @if ($item->check_in_deadline_at)<span class="text-xs text-gray-500 dark:text-gray-400">Deadline {{ $item->check_in_deadline_at->timezone(config('repair_reservations.timezone'))->format('g:i A') }}</span>@endif
                            </form>
                        @endif
                        @unless ($repair)
                            <form method="POST" action="{{ route('owner.repair-reservations.cancel', $item) }}" class="mt-3 flex gap-2">@csrf
                                <input name="reason" required maxlength="255" placeholder="Reason for cancelling" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                                <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 text-sm font-semibold">Cancel appointment</button></form>
                        @endunless
                    @endif
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-8 text-center text-gray-500 dark:text-gray-400">Nothing here.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</div>
</x-app-layout>
