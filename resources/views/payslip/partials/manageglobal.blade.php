<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Global Payslips
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                View all issued payslips.
            </p>
        </div>
    </header>
            
    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Employee</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hours Worked</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hourly Rate</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Adjustments</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Net Pay</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Issued</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900">
                @forelse($payslips as $p)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->user->first_name}} {{ $p->user->middle_name}} {{ $p->user->last_name}}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->period_from->format('F j, Y') }} — {{ $p->period_to->format('F j, Y') }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->hours_worked }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->hourly_rate, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->adjustments, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->net_pay, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->issue_date->format('F j, Y') }}</td>
                    </tr>
                @empty
                    <tr class="bg-white dark:bg-gray-900">
                        <td colspan="8" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                            No payslips found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>