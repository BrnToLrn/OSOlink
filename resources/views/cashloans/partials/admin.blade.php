<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Global Cash Loans
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Approve, reject, mark ongoing, or mark paid cash loans.
            </p>
        </div>
    </header>

    <div class="mt-6 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <form method="GET" action="{{ request()->url() }}" class="flex flex-wrap gap-3 items-center">
            <x-text-input type="text" name="search" placeholder="Search user, remarks..." value="{{ request('search') }}" class="w-full sm:w-64" />
            <select name="status" class="w-full sm:w-44 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">All Status</option>
                @foreach(['Pending','Approved','Rejected','Active','Fully Paid','Cancelled'] as $st)
                    <option value="{{ strtolower($st) }}" {{ request('status')===strtolower($st) ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
            <select name="sort" class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="date_requested" {{ request('sort')=='date_requested' ? 'selected' : '' }}>Date Requested</option>
                <option value="amount" {{ request('sort')=='amount' ? 'selected' : '' }}>Amount</option>
                <option value="type" {{ request('sort')=='type' ? 'selected' : '' }}>Type</option>
                <option value="user" {{ request('sort')=='user' ? 'selected' : '' }}>User</option>
            </select>
            <select name="order" class="w-full sm:w-32 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="asc" {{ request('order')=='asc' ? 'selected' : '' }}>Ascending</option>
                <option value="desc" {{ request('order')=='desc' ? 'selected' : '' }}>Descending</option>
            </select>
            <div class="flex items-center gap-4">
                <x-primary-button>Apply</x-primary-button>
                @if (session('admin_update_success'))
                    <p class="text-sm text-green-600 dark:text-green-400">{{ session('admin_update_success') }}</p>
                @endif
                @if (session('admin_update_error'))
                    <p class="text-sm text-red-600 dark:text-red-400">{{ session('admin_update_error') }}</p>
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
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Date Requested</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Amount</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Pay Periods</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900 text-sm">
                @forelse ($loans as $loan)
                    @php
                        $status = (string)($loan->status ?? 'Pending');
                        $s = strtolower($status);
                        $statusClass = $s === 'approved' ? 'text-green-600 dark:text-green-400'
                                     : ($s === 'rejected' ? 'text-red-600 dark:text-red-400'
                                     : ($s === 'active' ? 'text-blue-600 dark:text-blue-400'
                                     : ($s === 'fully paid' ? 'text-emerald-600 dark:text-emerald-400'
                                     : 'text-yellow-600 dark:text-yellow-400')));
                        $lockedApproval = in_array($status, ['Active','Fully Paid'], true);
                        $canMarkActive  = in_array($status, ['Pending','Approved'], true);
                        $canMarkPaid    = in_array($status, ['Active','Approved'], true);
                    @endphp
                    <tr>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $loan->user->first_name }} {{ $loan->user->middle_name }} {{ $loan->user->last_name }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ $loan->type }}</td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $loan->date_requested ? \Carbon\Carbon::parse($loan->date_requested)->format('F j, Y') : 'N/A' }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            CA$ {{ number_format((float)($loan->amount ?? 0), 2) }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ (int)$loan->pay_periods }} {{ (int)$loan->pay_periods === 1 ? 'period' : 'periods' }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span class="{{ $statusClass }}">{{ $status }}</span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <div class="inline-flex items-center justify-center gap-3">
                                <a href="{{ route('cashloans.show', $loan) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>

                                @if(!$lockedApproval)
                                    <form action="{{ route('cashloans.approve', $loan) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">Approve</button>
                                    </form>
                                    <form action="{{ route('cashloans.reject', $loan) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline">Reject</button>
                                    </form>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 select-none">Approval disabled</span>
                                @endif

                                <form action="{{ route('cashloans.activate', $loan) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit"
                                            class="text-blue-600 dark:text-blue-400 hover:underline {{ $canMarkActive ? '' : 'opacity-40 cursor-not-allowed pointer-events-none' }}"
                                            {{ $canMarkActive ? '' : 'disabled' }}>
                                        Mark Ongoing
                                    </button>
                                </form>

                                <form action="{{ route('cashloans.paid', $loan) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit"
                                            class="text-emerald-600 dark:text-emerald-400 hover:underline {{ $canMarkPaid ? '' : 'opacity-40 cursor-not-allowed pointer-events-none' }}"
                                            {{ $canMarkPaid ? '' : 'disabled' }}>
                                        Mark Paid
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                            No cash loans found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>