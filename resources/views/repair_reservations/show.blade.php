<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('customer.dashboard', ['tab' => 'repairs']) }}" class="text-sm text-blue-600 dark:text-blue-400">&larr; Back to Track My Repairs</a>
        <h2 class="text-2xl font-bold mt-2 mb-1 text-gray-900 dark:text-white">Service request {{ $reservation->reference }}</h2>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-3 text-sm text-green-900 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 p-3 text-sm text-red-900 dark:text-red-200">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-5 sm:p-6 space-y-4">
            @if ($reservation->status === 'pending')
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
                    <strong>Awaiting owner approval.</strong> This is a request, not a confirmed appointment. You will be notified when the shop decides.
                </div>
            @elseif ($reservation->status === 'accepted')
                <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-900 dark:text-green-200">
                    <strong>Accepted.</strong> Your appointment is today at {{ $reservation->timeLabel() }}.
                </div>
            @elseif ($reservation->status === 'rejected')
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 p-4 text-sm text-red-900 dark:text-red-200">
                    <strong>Not accepted.</strong> Reason: {{ $reservation->decision_reason }}
                </div>
            @elseif ($reservation->status === 'expired')
                <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 p-4 text-sm text-gray-800 dark:text-gray-200">
                    <strong>Expired.</strong> The shop did not answer this request before its deadline, so it is no longer open. You can send a new request if there is still time today.
                </div>
            @elseif ($reservation->status === 'completed')
                <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-4 text-sm text-green-900 dark:text-green-200">
                    <strong>Completed.</strong> Your service is complete.
                </div>
            @else
                <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 p-4 text-sm text-gray-800 dark:text-gray-200">
                    <strong>Cancelled</strong>{{ $reservation->cancelled_by === 'owner' ? ' by the shop' : ($reservation->cancelled_by === 'system' ? ' automatically' : '') }}.
                    @if ($reservation->cancelled_by === 'system') No check-in was recorded before the check-in deadline. @endif
                    @if ($reservation->cancelled_by === 'owner' && $reservation->decision_reason) Reason: {{ $reservation->decision_reason }} @endif
                </div>
            @endif

            <dl class="grid sm:grid-cols-2 gap-3 text-sm text-gray-800 dark:text-gray-200">
                <div><dt class="text-xs text-gray-500 dark:text-gray-400">Requested time</dt><dd class="font-semibold">{{ $reservation->dateLabel() }}, {{ $reservation->timeLabel() }}</dd></div>
                <div><dt class="text-xs text-gray-500 dark:text-gray-400">Service</dt><dd class="font-semibold">{{ $reservation->serviceLabel() }}</dd></div>
                <div><dt class="text-xs text-gray-500 dark:text-gray-400">Motorcycle</dt><dd>{{ $reservation->bike_model }}</dd></div>
                <div><dt class="text-xs text-gray-500 dark:text-gray-400">Status</dt><dd>{{ $reservation->stageLabel() }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">Details you gave</dt><dd class="whitespace-pre-line">{{ $reservation->description }}</dd></div>
            </dl>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">History</h3>
                <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    @foreach ($reservation->events as $event)
                        <li>{{ $event->created_at->timezone('Asia/Manila')->format('M d, g:i A') }} — {{ $event->from_status === $event->to_status && $event->note ? $event->note : ucfirst($event->to_status) }}{{ $event->actor_role === 'system' ? ' (automatic)' : '' }}</li>
                    @endforeach
                </ul>
            </div>

            @if ($reservation->isActive() && $reservation->scheduled_at->isFuture())
                <form method="POST" action="{{ route('repair-reservations.cancel', $reservation->reference) }}" class="flex flex-wrap gap-2 items-end pt-2 border-t border-gray-100 dark:border-gray-700">
                    @csrf
                    <input name="reason" maxlength="255" placeholder="Reason (optional)" class="flex-1 min-w-[12rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 dark:text-red-300 text-sm font-semibold" onclick="return confirm('Cancel this request?')">Cancel request</button>
                </form>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
