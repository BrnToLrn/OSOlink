<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Personal Cash Loans
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Make, view, and search your own cash loans.
            </p>
        </div>
        <div class="flex items-center gap-4">
            @php
                $flashMessage =
                    session('update_success') ??
                    session('create_success') ??
                    session('remove_success') ??
                    session('blocked_create');

                $flashClass = '';
                if ($flashMessage) {
                    if (session('remove_success') || session('blocked_create')) {
                        $flashClass = 'text-red-600 dark:text-red-400';
                    } else {
                        $flashClass = 'text-green-600 dark:text-green-400';
                    }
                }
                $ongoing = $hasOngoing ?? false;
            @endphp

            @if($flashMessage)
               <p class="text-sm font-medium {{ $flashClass }}">{{ $flashMessage }}</p>
            @endif

            @if(!$ongoing)
                <a href="{{ route('cashloans.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    + Request Cash Loan
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 bg-gray-400 dark:bg-gray-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest opacity-60 cursor-not-allowed select-none"
                      title="You have an ongoing cash loan. Request a new one after it is fully paid.">
                    + Request Cash Loan
                </span>
            @endif
        </div>
    </header>

    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
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
                        $isPending = strcasecmp($status, 'Pending') === 0;
                        $statusClass = match(strtolower($status)) {
                            'approved' => 'text-green-600 dark:text-green-400',
                            'rejected' => 'text-red-600 dark:text-red-400',
                            'active'   => 'text-blue-600 dark:text-blue-400',
                            'fully paid' => 'text-emerald-600 dark:text-emerald-400',
                            default    => 'text-yellow-600 dark:text-yellow-400',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ $loan->type }}</td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $loan->date_requested ? \Carbon\Carbon::parse($loan->date_requested)->format('F j, Y') : 'N/A' }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            CA$ {{ number_format((float)$loan->amount, 2) }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ (int)$loan->pay_periods }} {{ (int)$loan->pay_periods === 1 ? 'period' : 'periods' }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium {{ $statusClass }}">
                            {{ ucfirst($status) }}
                        </td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            <div class="inline-flex items-center gap-3">
                                @if($isPending)
                                    <a href="{{ route('cashloans.edit', $loan) }}"
                                       class="text-green-600 dark:text-green-400 hover:underline">Edit</a>
                                    <form action="{{ route('cashloans.destroy', $loan) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <a aria-disabled="true"
                                       class="text-green-600 dark:text-green-400 opacity-40 cursor-not-allowed pointer-events-none select-none">
                                        Edit
                                    </a>
                                    <button type="button" disabled
                                            class="text-red-600 dark:text-red-400 opacity-40 cursor-not-allowed select-none">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                            No cash loans found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>