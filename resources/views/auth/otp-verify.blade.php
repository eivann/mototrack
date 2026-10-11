<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Thanks for signing in! We sent a 6-digit OTP to ') }} <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ auth()->user()->email }}</strong>. {{ __('Please enter it below to verify your account.') }}
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 p-3 rounded-xl border border-green-200 dark:border-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf

        <!-- OTP Code -->
        <div>
            <x-input-label for="otp_code" :value="__('Verification Code')" />

            <x-text-input id="otp_code" class="block mt-1 w-full text-center tracking-widest text-lg font-bold" type="text" name="otp_code" required autofocus autocomplete="one-time-code" maxlength="6" pattern="\d{6}" placeholder="------" />

            <x-input-error :messages="$errors->get('otp_code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="w-full sm:w-auto justify-center">
                {{ __('Verify Account') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('otp.resend') }}">
            @csrf
            <button type="submit" class="underline text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none">
                {{ __('Resend OTP') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 focus:outline-none">
                {{ __('Log Out / Cancel') }}
            </button>
        </form>
    </div>

    <details class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-left">
        <summary class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer select-none text-center font-medium">
            {{ __('Entered the wrong email? Update email address') }}
        </summary>
        <form method="POST" action="{{ route('otp.update-email') }}" class="mt-3 space-y-3">
            @csrf
            <div>
                <x-input-label for="new_email" :value="__('Correct Email Address')" />
                <x-text-input id="new_email" class="block mt-1 w-full text-sm" type="email" name="email" :value="old('email')" required placeholder="yourname@gmail.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>
            <div class="flex justify-end">
                <x-primary-button class="text-xs py-2">
                    {{ __('Update Email & Resend OTP') }}
                </x-primary-button>
            </div>
        </form>
    </details>
</x-guest-layout>
