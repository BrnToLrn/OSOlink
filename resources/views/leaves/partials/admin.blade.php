<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Global Leaves
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Approve, view, and search global leaves.
            </p>
        </div>
    </header>

    <!-- Filter / Sorter (added) -->
    <div class="mt-6 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <form method="GET" action="{{ request()->url() }}" class="flex flex-wrap gap-3 items-center">
            <!-- Search (by user name or reason) -->
            <x-text-input
                type="text"
                name="search"
                placeholder="Search user, reason..."
                value="{{ request('search') }}"
                class="w-full sm:w-64"
            />

            <!-- Status Filter -->
            <select name="status"
                class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                       bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">All Status</option>
                <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <!-- Sort Field -->
            <select name="sort"
                class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                       bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="start_date" {{ request('sort')=='start_date' ? 'selected' : '' }}>Start Date</option>
                <option value="end_date" {{ request('sort')=='end_date' ? 'selected' : '' }}>End Date</option>
                <option value="type" {{ request('sort')=='type' ? 'selected' : '' }}>Type</option>
                <option value="user" {{ request('sort')=='user' ? 'selected' : '' }}>User</option>
            </select>

            <!-- Sort Order -->
            <select name="order"
                class="w-full sm:w-32 rounded-lg border-gray-300 dark:border-gray-600 
                       bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="asc" {{ request('order')=='asc' ? 'selected' : '' }}>Ascending</option>
                <option value="desc" {{ request('order')=='desc' ? 'selected' : '' }}>Descending</option>
            </select>

            <div class="flex items-center gap-4">
                <x-primary-button>
                    Apply
                </x-primary-button>

                @if (session('admin_update_success'))
                    <p class="text-sm text-green-600 dark:text-green-400">{{ session('admin_update_success') }}</p>
                @endif
            </div>
        </form>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">User</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Type</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">End Date</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900 text-sm">
                @forelse ($leaves as $leave)
                    <tr>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $leave->user->first_name }} {{ $leave->user->middle_name }} {{ $leave->user->last_name }}
                        </td>

                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ $leave->type }}</td>

                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('F j, Y') : 'N/A' }}
                        </td>

                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('F j, Y') : 'N/A' }}
                        </td>

                        <td class="px-4 py-2 text-center">
                            @php
                                $status = $leave->status ?? 'Pending';
                                $s = strtolower($status);
                                $statusClass = $s === 'approved' ? 'text-green-600 dark:text-green-400'
                                             : ($s === 'rejected' ? 'text-red-600 dark:text-red-400'
                                             : 'text-yellow-600 dark:text-yellow-400');
                            @endphp
                            <span class="{{ $statusClass }}">{{ ucfirst($status) }}</span>
                        </td>

                        <td class="px-4 py-2 text-center">
                            <div class="inline-flex items-center justify-center gap-3">
                                <a href="{{ route('leaves.show', $leave) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>

                                <form action="{{ route('leaves.approve', $leave) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">Approve</button>
                                </form>

                                <form action="{{ route('leaves.reject', $leave) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline">Reject</button>
                                </form>

                                <form action="{{ route('leaves.pending', $leave) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:underline">Pending</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 text-center text-gray-500">No leaves found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>