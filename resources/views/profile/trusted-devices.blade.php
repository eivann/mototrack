<x-app-layout>
    <div class="py-10 bg-gray-50 dark:bg-gray-950 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('profile.edit') }}" class="text-sm text-indigo-600 dark:text-indigo-400">&larr; Back to profile</a>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white mt-2 mb-1">Remembered devices</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                Browsers where you chose "Remember this device" can skip the emailed code when you sign in with your password.
                Trust lasts {{ $days }} days and ends sooner if you change your password or email.
            </p>

            @if (session('status') === 'device-revoked')
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900">That device was removed. It will need the emailed code next time.</div>
            @elseif (session('status') === 'devices-revoked')
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-900">All remembered devices were removed.</div>
            @endif
            @if ($errors->trustedDevices->any())
                <div class="mb-4 text-red-600 text-sm">@foreach ($errors->trustedDevices->all() as $e)<div>• {{ $e }}</div>@endforeach</div>
            @endif

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($devices as $device)
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3" id="device-{{ $device->id }}">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">{{ $device->label ?: 'Browser' }}
                                @if ($device->id === $currentId)<span class="ml-2 text-xs font-bold text-green-700 bg-green-50 rounded px-2 py-0.5">This device</span>@endif</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Added {{ $device->created_at->timezone('Asia/Manila')->format('M d, Y') }}
                                &middot; last used {{ $device->last_used_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? '—' }}
                                &middot; expires {{ $device->expires_at->timezone('Asia/Manila')->format('M d, Y') }}</div>
                        </div>
                        <form method="POST" action="{{ route('profile.devices.destroy', $device->id) }}" class="flex gap-2 items-center">
                            @csrf @method('DELETE')
                            <input type="password" name="current_password" required autocomplete="current-password" placeholder="Your password" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                            <button class="px-3 py-2 rounded-lg border border-red-300 text-red-700 text-sm font-semibold">Remove</button>
                        </form>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-600 dark:text-gray-400">No remembered devices. {{ $enabled ? 'Tick "Remember this device" when you enter your emailed code to add one.' : '' }}</div>
                @endforelse
            </div>

            @if ($devices->count() > 0)
                <form method="POST" action="{{ route('profile.devices.destroy-all') }}" class="mt-4 flex gap-2 items-center">
                    @csrf @method('DELETE')
                    <input type="password" name="current_password" required autocomplete="current-password" placeholder="Your password" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-600">
                    <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold">Remove all devices</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
