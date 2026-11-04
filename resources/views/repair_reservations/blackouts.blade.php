<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('owner.repair-reservations.index') }}" class="text-sm text-blue-600 dark:text-blue-400">&larr; Service requests</a>
        <h2 class="text-2xl font-bold mt-2 mb-1 text-gray-900 dark:text-white">Blackouts (closed times)</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Times when customers cannot request, and you cannot accept, a service appointment (Philippine time). A blackout never cancels, rejects or expires an existing request &mdash; affected ones are only flagged.</p>

        @if (session('success'))<div class="mb-3 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-900">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="mb-3 text-red-600 text-sm"><ul>@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ route('owner.repair-blackouts.store') }}" x-data="{ type: '{{ old('type', 'day') }}' }"
              class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-4 shadow-sm space-y-3 mb-6">
            @csrf
            <fieldset>
                <legend class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">What to close</legend>
                <div class="flex flex-wrap gap-3 text-sm text-gray-800 dark:text-gray-200">
                    <label class="flex items-center gap-1"><input type="radio" name="type" value="day" x-model="type"> Whole day</label>
                    <label class="flex items-center gap-1"><input type="radio" name="type" value="range" x-model="type"> Time range</label>
                    <label class="flex items-center gap-1"><input type="radio" name="type" value="slot" x-model="type"> One appointment slot</label>
                </div>
            </fieldset>
            <div>
                <label for="bo-date" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Date</label>
                <input id="bo-date" type="date" name="date" required min="{{ $today }}" value="{{ old('date', $today) }}" class="w-full sm:w-auto rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
            <div x-show="type === 'slot'" x-cloak>
                <label for="bo-time" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Appointment time</label>
                <select id="bo-time" name="time" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    @foreach ($times as [$value, $label])<option value="{{ $value }}" {{ old('time') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Closes this slot (30 minutes). A one-hour booking that would run into it is blocked too.</p>
            </div>
            <div x-show="type === 'range'" x-cloak class="flex flex-wrap gap-3">
                <div><label for="bo-from" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">From</label>
                    <input id="bo-from" type="time" name="start_time" value="{{ old('start_time') }}" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></div>
                <div><label for="bo-to" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Until (exclusive)</label>
                    <input id="bo-to" type="time" name="end_time" value="{{ old('end_time') }}" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></div>
            </div>
            <div>
                <label for="bo-reason" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Reason (only you see this)</label>
                <input id="bo-reason" name="reason" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Holiday, mechanic training" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-bold">Add blackout</button>
        </form>

        @if ($affected->isNotEmpty())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4 text-sm text-red-900 dark:text-red-200">
                <p class="font-bold mb-1">Existing requests inside a blackout (not changed)</p>
                <ul class="space-y-1">
                    @foreach ($affected as $r)
                        <li><a class="underline" href="{{ route('owner.repair-reservations.index', ['tab' => $r->status === 'pending' ? 'pending' : 'accepted']) }}#rr-{{ $r->id }}">{{ $r->reference }}</a>
                            &middot; {{ $r->dateLabel() }}, {{ $r->timeLabel() }} &middot; {{ $r->status === 'pending' ? 'pending (cannot be accepted)' : 'accepted (stays accepted)' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h3 class="font-bold text-gray-900 dark:text-white mb-2">Upcoming and current</h3>
        <div class="space-y-2 mb-6">
            @forelse ($upcoming as $b)
                <div class="flex flex-wrap items-center justify-between gap-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-3">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $b->label() }}</div>
                        @if ($b->reason)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $b->reason }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('owner.repair-blackouts.destroy', $b) }}" onsubmit="return confirm('Remove this blackout?');">@csrf @method('DELETE')
                        <button class="px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-sm font-semibold">Remove</button></form>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No blackouts set.</p>
            @endforelse
        </div>

        @if ($past->isNotEmpty())
            <h3 class="font-bold text-gray-900 dark:text-white mb-2">Past (history)</h3>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                @foreach ($past as $b)<li>{{ $b->label() }}{{ $b->reason ? ' — '.$b->reason : '' }}</li>@endforeach
            </ul>
        @endif
    </div>
</div>
</x-app-layout>
