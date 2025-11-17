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
        <div class="flex flex-wrap items-end gap-4">
            <form method="GET" action="{{ url()->current() }}">
                <x-input-label for="ps_period_global" value="Period" />
                <select id="ps_period_global" name="ps_period_global" class="mt-1 w-full max-w-xl rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $monthName = \Carbon\Carbon::create(null, $m, 1)->format('F');
                            $mm = sprintf('%02d', $m);
                            $endDay = \Carbon\Carbon::create($yearNow, $m, 1)->endOfMonth()->day;
                            $isCurrentMonth = ($m === $monthNow);
                            $selFirst = $isCurrentMonth && $defaultHalf === 1 ? 'selected' : '';
                            $selSecond = $isCurrentMonth && $defaultHalf === 2 ? 'selected' : '';
                            $firstStart = "{$yearNow}-{$mm}-01";
                            $firstEnd = "{$yearNow}-{$mm}-15";
                            $secondStart = "{$yearNow}-{$mm}-16";
                            $secondEnd = "{$yearNow}-{$mm}-" . sprintf('%02d', $endDay);
                            $selectedValue = request('ps_period');
                            if ($selectedValue) {
                                $selFirst = $selectedValue === "$firstStart|$firstEnd" ? 'selected' : '';
                                $selSecond = $selectedValue === "$secondStart|$secondEnd" ? 'selected' : '';
                            }
                        @endphp
                        <option value="{{ $firstStart }}|{{ $firstEnd }}" {{ $selFirst }}>
                            {{ $monthName }} 1, {{ $yearNow }} – {{ $monthName }} 15, {{ $yearNow }}
                        </option>
                        <option value="{{ $secondStart }}|{{ $secondEnd }}" {{ $selSecond }}>
                            {{ $monthName }} 16, {{ $yearNow }} – {{ $monthName }} {{ $endDay }}, {{ $yearNow }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>
    </header>
            
    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Employee</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hours Worked</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Adjustments</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Cash Loan</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Net Pay</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Issued</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900">
                @forelse($payslips as $p)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $p->user->first_name }} {{ $p->user->middle_name }} {{ $p->user->last_name }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ number_format($p->hours_worked, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->adjustments, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->cash_loan_period_deduction ?? 0, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">CA${{ number_format($p->net_pay, 2) }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $p->issue_date->format('F j, Y') }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            <button
                                type="button"
                                class="text-blue-600 dark:text-blue-400 hover:underline btn-edit-payslip"
                                title="Edit"
                                data-id="{{ $p->id }}"
                                data-update-url="{{ route('payslip.update', $p) }}"
                                data-user-id="{{ $p->user_id }}"
                                data-user-name="{{ trim($p->user->first_name . ' ' . ($p->user->middle_name ?? '') . ' ' . $p->user->last_name) }}"
                                data-job-type="{{ $p->user->job_type ?? '' }}"
                                data-hours-worked="{{ number_format($p->hours_worked, 2, '.', '') }}"
                                data-hourly-rate="{{ number_format($p->hourly_rate, 2, '.', '') }}"
                                data-gross-pay="{{ number_format($p->gross_pay, 2, '.', '') }}"
                                data-adjustments="{{ number_format($p->adjustments, 2, '.', '') }}"
                                data-net-pay="{{ number_format($p->net_pay, 2, '.', '') }}"
                                data-cash-loan-deduction="{{ number_format($p->cash_loan_period_deduction ?? 0, 2, '.', '') }}"
                                data-period-from="{{ $p->period_from->format('Y-m-d') }}"
                                data-period-to="{{ $p->period_to->format('Y-m-d') }}"
                                data-issue-date="{{ $p->issue_date->format('Y-m-d') }}"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="text-red-600 dark:text-red-400 hover:underline btn-delete-payslip"
                                title="Delete"
                                data-destroy-url="{{ route('payslip.destroy', $p) }}"
                            >
                                Delete
                            </button>
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

<div id="payslipModalGlobal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-labelledby="payslipModalTitle" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative w-full max-w-3xl px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <header class="flex items-center justify-between">
                <div>
                    <h2 id="payslipModalTitle" class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Add Payslip
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Issue a payslip for an employee.
                    </p>
                </div>
                <button
                    type="button"
                    id="payslipModalClose"
                    class="h-10 w-10 flex items-center justify-center rounded-full text-3xl leading-none text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    aria-label="Close"
                    title="Close">
                    &times;
                </button>
            </header>

            <form id="payslipFormGlobal" action="{{ route('payslip.store') }}" method="POST" class="space-y-6 mt-4">
                @csrf
                <input type="hidden" id="payslipFormMethod" name="_method" value="POST" />

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="employee_name" :value="__('Employee')" />
                        <x-text-input id="employee_name" name="employee_name" type="text" class="mt-1 block w-full cursor-not-allowed opacity-75" value="" disabled />
                        <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="job_type_display" :value="__('Job Type')" />
                        <x-text-input id="job_type_display" name="job_type_display" type="text" class="mt-1 block w-full cursor-not-allowed opacity-75" value="" disabled />
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="hours_worked" :value="__('Hours Worked')" />
                        <x-text-input id="hours_worked" name="hours_worked_display" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="0.00" disabled />
                    </div>

                    <div class="flex-1">
                        <x-input-label for="hourly_rate" :value="__('Hourly Rate')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="hourly_rate" name="hourly_rate_display" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="0.00" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="cash_loan_deduction" :value="__('Cash Loan Deduction')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="cash_loan_deduction" name="cash_loan_deduction_display" type="text" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="0.00" disabled />
                        </div>
                    </div>

                    <div class="flex-1">
                        <x-input-label for="gross_pay" :value="__('Gross Pay')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="gross_pay" name="gross_pay_display" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="0.00" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="adjustments" :value="__('Adjustments')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="adjustments" name="adjustments" type="number" step="0.01" class="mt-1 block w-full pl-14" value="0.00" />
                        </div>
                    </div>

                    <div class="flex-1">
                        <x-input-label for="net_pay" :value="__('Net Pay')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="net_pay" name="net_pay_display" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="0.00" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button id="payslipSaveBtn" type="submit">
                        Save Payslip
                    </x-primary-button>
                </div>

                <!-- Hidden fields submitted to backend -->
                <input type="hidden" name="user_id" id="user_id" value="">
                <input type="hidden" name="period_from" id="period_from" value="">
                <input type="hidden" name="period_to" id="period_to" value="">
                <input type="hidden" name="issue_date" id="issue_date" value="">
                <input type="hidden" name="hours_worked" id="hours_worked_hidden" value="0.00">
                <input type="hidden" name="hourly_rate" id="hourly_rate_hidden" value="0.00">
                <input type="hidden" name="gross_pay" id="gross_pay_hidden" value="0.00">
                <input type="hidden" name="net_pay" id="net_pay_hidden" value="0.00">
            </form>
        </div>
    </div>
</div>

<!-- Delete helper form -->
<form id="payslipDeleteForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('payslipModalGlobal');
    const closeBtn = document.getElementById('payslipModalClose');

    const form = document.getElementById('payslipFormGlobal');
    const formMethod = document.getElementById('payslipFormMethod');

    // Visible inputs
    const employeeNameInput = document.getElementById('employee_name');
    const jobTypeInput = document.getElementById('job_type_display');
    const hoursWorkedInput = document.getElementById('hours_worked');
    const hourlyRateInput = document.getElementById('hourly_rate');
    const cashLoanInput = document.getElementById('cash_loan_deduction');
    const grossPayInput = document.getElementById('gross_pay');
    const adjustmentsInput = document.getElementById('adjustments');
    const netPayInput = document.getElementById('net_pay');

    // Hidden payload
    const userIdHidden = document.getElementById('user_id');
    const periodFromHidden = document.getElementById('period_from');
    const periodToHidden = document.getElementById('period_to');
    const issueDateHidden = document.getElementById('issue_date');
    const hoursHidden = document.getElementById('hours_worked_hidden');
    const rateHidden = document.getElementById('hourly_rate_hidden');
    const grossHidden = document.getElementById('gross_pay_hidden');
    const netHidden = document.getElementById('net_pay_hidden');

    function calc() {
        const rate = parseFloat(hourlyRateInput.value) || 0;
        const hours = parseFloat(hoursWorkedInput.value) || 0;
        const adj = parseFloat(adjustmentsInput.value) || 0;

        const gross = rate * hours;
        const net = gross + adj;

        grossPayInput.value = gross.toFixed(2);
        netPayInput.value = net.toFixed(2);
        grossHidden.value = gross.toFixed(2);
        netHidden.value = net.toFixed(2);
        hoursHidden.value = hours.toFixed(2);
        rateHidden.value = rate.toFixed(2);
    }

    adjustmentsInput?.addEventListener('input', calc);

    // Event delegation for Edit/Delete buttons
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-payslip');
        const delBtn = e.target.closest('.btn-delete-payslip');

        if (editBtn) {
            // Switch form to UPDATE
            form.action = editBtn.dataset.updateUrl;
            formMethod.value = 'PUT';
            document.getElementById('payslipModalTitle').textContent = 'Edit Payslip';

            // Fill visible fields
            employeeNameInput.value = editBtn.dataset.userName || '';
            jobTypeInput.value = editBtn.dataset.jobType || '';
            hoursWorkedInput.value = (editBtn.dataset.hoursWorked || '0');
            hourlyRateInput.value = (editBtn.dataset.hourlyRate || '0');
            cashLoanInput.value = (editBtn.dataset.cashLoanDeduction || '0');
            grossPayInput.value = (editBtn.dataset.grossPay || '0');
            adjustmentsInput.value = (editBtn.dataset.adjustments || '0');
            netPayInput.value = (editBtn.dataset.netPay || '0');

            // Fill hidden fields
            userIdHidden.value = editBtn.dataset.userId || '';
            periodFromHidden.value = editBtn.dataset.periodFrom || '';
            periodToHidden.value = editBtn.dataset.periodTo || '';
            issueDateHidden.value = editBtn.dataset.issueDate || '';

            hoursHidden.value = editBtn.dataset.hoursWorked || '0';
            rateHidden.value = editBtn.dataset.hourlyRate || '0';
            grossHidden.value = editBtn.dataset.grossPay || '0';
            netHidden.value = editBtn.dataset.netPay || '0';

            calc();
            modal.classList.remove('hidden');
            return;
        }

        if (delBtn) {
            const delUrl = delBtn.dataset.destroyUrl;
            if (!delUrl) return;
            if (!confirm('Delete this payslip? This will detach related time logs.')) return;

            const deleteForm = document.getElementById('payslipDeleteForm');
            deleteForm.action = delUrl;
            deleteForm.submit();
        }
    });

    // Close modal
    closeBtn?.addEventListener('click', () => modal.classList.add('hidden'));
    modal.querySelector('.absolute.inset-0')?.addEventListener('click', () => modal.classList.add('hidden'));
});
</script>