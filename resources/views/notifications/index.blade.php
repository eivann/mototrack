<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 pb-12">
        <!-- Premium Header -->
        <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center shadow-sm">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">Notifications</h2>
                            <p class="text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Activity Center</p>
                        </div>
                    </div>
                    
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <form action="{{ route('notifications.read_all') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 px-4 py-2 rounded-xl text-xs font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <i data-lucide="check-check" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Mark all as read</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
                
                @if($notifications->count() > 0)
                    <div class="divide-y divide-gray-50 dark:divide-gray-800">
                        @foreach($notifications as $notification)
                            <div class="flex items-start gap-4 p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors {{ $notification->unread() ? 'bg-indigo-50/30 dark:bg-indigo-900/10' : '' }}">
                                <!-- Icon -->
                                <div class="shrink-0 mt-1">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $notification->unread() ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-400' }}">
                                        <i data-lucide="{{ str_contains(strtolower($notification->data['title'] ?? ''), 'repair') ? 'wrench' : 'package' }}" class="w-5 h-5"></i>
                                    </div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start gap-4">
                                        <div>
                                            <h4 class="text-sm font-bold {{ $notification->unread() ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                                                {{ $notification->data['title'] ?? 'Notification' }}
                                                @if($notification->unread())
                                                    <span class="inline-block w-2 h-2 rounded-full bg-indigo-500 ml-2"></span>
                                                @endif
                                            </h4>
                                            <p class="text-sm mt-1 {{ $notification->unread() ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500' }}">
                                                {{ $notification->data['message'] ?? '' }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-4 flex items-center gap-4">
                                        @if(!empty($notification->data['link']))
                                            <a href="{{ $notification->data['link'] }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                                View details
                                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                            </a>
                                        @endif

                                        @if($notification->unread())
                                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors focus:outline-none">
                                                    Mark as read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($notifications->hasPages())
                        <div class="p-6 border-t border-gray-100 dark:border-gray-800">
                            {{ $notifications->links() }}
                        </div>
                    @endif

                @else
                    <div class="p-16 text-center">
                        <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-3xl flex items-center justify-center mx-auto mb-6 text-gray-400">
                            <i data-lucide="bell-off" class="w-10 h-10"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No notifications yet</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">When you have new updates, they will appear here.</p>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
