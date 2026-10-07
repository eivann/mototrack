<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Thanks for signing in! Before getting started, could you verify your email address by entering the 6-digit OTP we just emailed to you?') }}
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
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
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="mt-4">
        @csrf
        <div class="flex items-center justify-center mt-4">
            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                {{ __('Resend OTP') }}
            </button>
        </div>
    </form>
</x-guest-layout>
