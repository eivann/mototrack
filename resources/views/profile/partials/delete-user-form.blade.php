<section class="space-y-5" x-data="{ confirmingDeletion: false }">
    <div>
        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
            Once your account is deleted, all of its resources and data will be <strong class="text-red-600 dark:text-red-400">permanently deleted</strong>. Please download any data you wish to retain before proceeding.
        </p>
    </div>

    <button
        @click="confirmingDeletion = true"
        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 font-black text-sm rounded-2xl border border-red-200 dark:border-red-800 transition-all uppercase tracking-widest group">
        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Delete Account
    </button>

    {{-- Confirmation Modal --}}
    <div
        x-show="confirmingDeletion || {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-cloak>
        <div @click.outside="confirmingDeletion = false"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-md bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">

            <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="font-black text-gray-900 dark:text-white">Delete Account?</h3>
                    <p class="text-xs text-gray-400 mt-0.5">This action cannot be undone</p>
                </div>
            </div>

            <form method="post" action="{{ route('profile.destroy') }}" class="p-6 space-y-5">
                @csrf
                @method('delete')

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Enter your password to confirm you want to permanently delete your account and all associated data.
                </p>

                <div>
                    <label for="delete_password" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Your Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <input id="delete_password" name="password" type="password"
                            placeholder="Enter your password"
                            class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-semibold text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition-all">
                    </div>
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-red-500 text-xs" />
                </div>

                <div class="flex gap-3 justify-end pt-2">
                    <button type="button" @click="confirmingDeletion = false"
                        class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-black rounded-2xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all uppercase tracking-widest">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-black rounded-2xl shadow-lg shadow-red-500/25 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest">
                        Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
