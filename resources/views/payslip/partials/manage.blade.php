<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Personal Payslips
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                View your own issued payslips.
            </p>
        </div>
    </header>
            
    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Net Pay</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Total Deductions</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Gross Pay</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Issued</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900">
                @forelse($payslips as $p)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->period_from->format('Y-m-d') }} — {{ $p->period_to->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${{ number_format($p->net_pay, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${{ number_format($p->deductions, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">${{ number_format($p->gross_pay, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ optional($p->issued_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($p->status) }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('payslip.show', $p) }}" class="text-indigo-600">View</a>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white dark:bg-gray-900">
                        <td colspan="7" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                            No payslips found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>