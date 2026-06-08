<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-10 px-4">
        <div class="max-w-7xl mx-auto space-y-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tight italic">System Audit Trail</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Track and monitor all operations and record mutations.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <form method="GET" action="{{ route('admin.audit_logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">User</label>
                        <select name="user_id" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Action</label>
                        <select name="action" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Actions</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted (Hard)</option>
                            <option value="soft_deleted" {{ request('action') == 'soft_deleted' ? 'selected' : '' }}>Deleted (Soft)</option>
                            <option value="restored" {{ request('action') == 'restored' ? 'selected' : '' }}>Restored</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Module Type</label>
                        <select name="model_type" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                            <option value="">All Modules</option>
                            @foreach($models as $class => $name)
                                <option value="{{ $class }}" {{ request('model_type') == $class ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border border-gray-200 dark:border-gray-800 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    </div>
                    <div class="md:col-span-5 flex justify-end gap-3 mt-2">
                        <a href="{{ route('admin.audit_logs.index') }}" class="px-5 py-3 border border-gray-250 dark:border-gray-800 rounded-xl font-bold text-xs uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-850">Clear Filters</a>
                        <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-100 dark:shadow-none">Filter Logs</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-400 uppercase font-black text-xs tracking-wider border-b border-gray-100 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4">Timestamp</th>
                                <th class="px-6 py-4">Operator</th>
                                <th class="px-6 py-4">Action</th>
                                <th class="px-6 py-4">Model & ID</th>
                                <th class="px-6 py-4">Details (Old vs New)</th>
                                <th class="px-6 py-4">Network Info</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-400">
                                        {{ $log->created_at->format('Y-m-d h:i:s A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $log->user->name ?? 'System/Console' }}</div>
                                        <div class="text-xs text-gray-400">{{ $log->user->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                            {{ $log->action === 'created' ? 'bg-emerald-55 dark:bg-emerald-950/40 text-emerald-600' : '' }}
                                            {{ $log->action === 'updated' ? 'bg-amber-55 dark:bg-amber-950/40 text-amber-600' : '' }}
                                            {{ $log->action === 'deleted' || $log->action === 'soft_deleted' ? 'bg-rose-55 dark:bg-rose-950/40 text-rose-600' : '' }}
                                            {{ $log->action === 'restored' ? 'bg-indigo-55 dark:bg-indigo-950/40 text-indigo-600' : '' }}
                                        ">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ class_basename($log->auditable_type) }}
                                        </div>
                                        <div class="text-xs text-gray-400">ID: {{ $log->auditable_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <div class="text-xs space-y-1">
                                            @if($log->action === 'created')
                                                <div class="text-emerald-500 font-bold">Populated parameters.</div>
                                            @elseif($log->action === 'deleted' || $log->action === 'soft_deleted')
                                                <div class="text-rose-500 font-bold">Deleted record values.</div>
                                            @elseif($log->action === 'restored')
                                                <div class="text-indigo-500 font-bold">Restored original record values.</div>
                                            @else
                                                @if(!empty($log->old_values))
                                                    @foreach($log->old_values as $key => $val)
                                                        <div>
                                                            <span class="font-semibold text-gray-400">{{ $key }}:</span>
                                                            <span class="text-red-400 line-through">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                                            &rarr;
                                                            <span class="text-emerald-400 font-bold">{{ is_array($log->new_values[$key]) ? json_encode($log->new_values[$key]) : $log->new_values[$key] }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400">
                                        <div>IP: <span class="font-semibold text-gray-600 dark:text-gray-200">{{ $log->ip_address }}</span></div>
                                        <div class="truncate max-w-[120px]" title="{{ $log->user_agent }}">UA: {{ $log->user_agent }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-semibold">
                                        No audit entries discovered matching selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
