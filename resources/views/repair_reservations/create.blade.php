<x-app-layout>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6 px-4">
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('customer.dashboard', ['tab' => 'repairs']) }}" class="text-sm text-blue-600 dark:text-blue-400">&larr; Back to Track My Repairs</a>
        <h2 class="text-2xl font-bold mt-2 mb-1 text-gray-900 dark:text-white">Request a repair / service appointment</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Same-day only &middot; {{ $todayLabel }} &middot; appointments start {{ $open }}–{{ $close }} (Philippine time)</p>

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 p-3 text-sm text-red-900 dark:text-red-200">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-red-600 dark:text-red-400 text-sm"><ul>@foreach ($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>
        @endif

        @if ($existing)
            <div class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
                You already have an active request for today ({{ $existing->reference }}, {{ $existing->timeLabel() }}, {{ strtolower($existing->label()) }}).
                <a class="underline font-semibold" href="{{ route('repair-reservations.show', $existing->reference) }}">View it</a>
            </div>
        @else
        <form method="POST" action="{{ route('repair-reservations.store') }}" x-data="{ busy: false }" @submit="busy = true"
              class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-2xl p-5 sm:p-6 space-y-5">
            @csrf
            <input type="hidden" name="appointment_date" value="{{ $today }}">

            <fieldset x-data="{ t: '{{ old('appointment_time') }}' }">
                <legend class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Preferred time today</legend>
                @if (collect($slots)->where('available', true)->isEmpty())
                    <p class="text-sm text-gray-600 dark:text-gray-400">No more times are available today. Please visit the shop or try again tomorrow.</p>
                @endif
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach ($slots as $slot)
                        <label class="block rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-1">
                            <input type="radio" name="appointment_time" value="{{ $slot['value'] }}" x-model="t" class="sr-only"
                                   {{ $slot['available'] ? '' : 'disabled' }}>
                            @if ($slot['available'])
                                <span class="block text-center text-sm rounded-lg border px-2 py-3 cursor-pointer"
                                      :class="t === '{{ $slot['value'] }}' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 dark:border-gray-600 text-gray-800 dark:text-gray-200'">{{ $slot['label'] }}</span>
                            @else
                                <span class="block text-center text-sm rounded-lg border px-2 py-3 border-gray-200 dark:border-gray-600 text-gray-400 line-through opacity-50 cursor-not-allowed">{{ $slot['label'] }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">Times that have passed, are closed or are fully booked are crossed out.</p>
            </fieldset>

            <div>
                <label for="service_type" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Service type</label>
                <select id="service_type" name="service_type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    @foreach ($serviceTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('service_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="bike_model" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Motorcycle (make / model)</label>
                <input id="bike_model" name="bike_model" type="text" required maxlength="80" value="{{ old('bike_model') }}" placeholder="e.g. Honda Click 125"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">What needs to be done?</label>
                <textarea id="description" name="description" required minlength="10" maxlength="1000" rows="4" placeholder="Describe the problem or the service you want."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">{{ old('description') }}</textarea>
            </div>

            @include('repair_reservations.partials.terms')

            <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded border-gray-300" {{ old('accept_terms') ? 'checked' : '' }}>
                <span>I understand this is a <strong>request</strong>, not a confirmed appointment. It is only confirmed once the shop owner accepts it, and the owner may decline it when the shop is full. I will be notified of the decision.</span>
            </label>

            <button type="submit" :disabled="busy" class="w-full sm:w-auto px-5 py-2.5 rounded-lg bg-blue-600 text-white font-semibold disabled:opacity-60">Send request</button>
        </form>
        @endif
    </div>
</div>
</x-app-layout>
