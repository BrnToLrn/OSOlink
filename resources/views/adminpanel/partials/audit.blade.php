<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Audit Logs
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Track admin actions performed in the system.
        </p>
    </header>

    <!-- Filters -->
    <div class="mt-6 overflow-x-auto">
        <div class="flex flex-wrap items-center gap-3 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <form method="GET" action="{{ route('adminpanel.admin') }}" class="flex flex-wrap gap-3 w-full">
                <!-- User Filter -->
                <x-text-input 
                    type="text" 
                    name="log_user" 
                    placeholder="Filter by user..." 
                    value="{{ request('log_user') }}" 
                    class="w-full sm:w-64"
                />

                <!-- Action Filter -->
                <x-text-input 
                    type="text" 
                    name="log_action" 
                    placeholder="Filter by action..." 
                    value="{{ request('log_action') }}" 
                    class="w-full sm:w-64"
                />

                <!-- Sort Field -->
                <select name="log_sort" 
                    class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                        focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="created_at" {{ request('log_sort')=='created_at' ? 'selected' : '' }}>Date</option>
                    <option value="action" {{ request('log_sort')=='action' ? 'selected' : '' }}>Action</option>
                </select>

                <!-- Sort Order -->
                <select name="log_order" 
                    class="w-full sm:w-32 rounded-lg border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                        focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="desc" {{ request('log_order')=='desc' ? 'selected' : '' }}>Descending</option>
                    <option value="asc" {{ request('log_order')=='asc' ? 'selected' : '' }}>Ascending</option>
                </select>

                <!-- Apply Button -->
                <x-primary-button>
                    Apply
                </x-primary-button>
            </form>
        </div>

        <!-- Table -->
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">User</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Action</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Target</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">When</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900">
                @foreach($logs as $log)
                    <tr>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $log->user->first_name ?? 'System' }} {{ $log->user->middle_name ?? '' }} {{ $log->user->last_name ?? '' }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $log->action }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $log->target?->email }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $logs->appends(request()->except('logs_page'))->links() }}
    </div>
</section>
