@php
    // Use provided collections if available
    $globalPayslips = $allPayslips ?? $payslips ?? collect();
    $monthNow = (int) now()->format('n');
    $yearNow = (int) now()->format('Y');
    $defaultHalf = (int) (now()->format('j') <= 15 ? 1 : 2);
@endphp

<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Create Payslips
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Make employee payslips by period, fixed to 1–15 and 16–30/31.
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            <form method="GET" action="{{ url()->current() }}">
                <x-input-label for="ps_period" value="Period" />
                <select id="ps_period" name="ps_period" class="mt-1 w-full max-w-xl rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
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
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Job Type</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hourly Rate</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hours Worked</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody id="ps_rows" class="divide-gray-200 dark:bg-gray-900">
                @php
                    // Resolve selected period from dropdown (value is "YYYY-MM-DD|YYYY-MM-DD")
                    $selected = request('ps_period') ?? null;
                    if ($selected && str_contains($selected, '|')) {
                        [$selStart, $selEnd] = explode('|', $selected, 2);
                    } else {
                        $mm = sprintf('%02d', $monthNow);
                        $endDay = \Carbon\Carbon::create($yearNow, $monthNow, 1)->endOfMonth()->day;
                        $selStart = $defaultHalf === 1 ? "{$yearNow}-{$mm}-01" : "{$yearNow}-{$mm}-16";
                        $selEnd = $defaultHalf === 1 ? "{$yearNow}-{$mm}-15" : "{$yearNow}-{$mm}-" . sprintf('%02d', $endDay);
                    }

                    $rows = $periodEmployees ?? collect();
                @endphp

                @forelse($rows as $row)
                    @php
                        $user = is_array($row) ? ($row['user'] ?? null) : ($row->user ?? $row);
                        $hours = is_array($row) ? ($row['hours'] ?? 0) : ($row->hours ?? 0);

                        // Build "First [Middle] Last"
                        $parts = [
                            trim(optional($user)->first_name ?? ''),
                            trim(optional($user)->middle_name ?? ''),
                            trim(optional($user)->last_name ?? ''),
                        ];
                        $fullName = trim(implode(' ', array_filter($parts)));

                        $jobType = $user->job_type ?? '—';
                        $hourlyRate = isset($user->hourly_rate) ? number_format((float)$user->hourly_rate, 2) : '0.00';
                    @endphp
                    <tr class="ps-row"
                        data-period-from="{{ $selStart }}"

                        data-period-to="{{ $selEnd }}">
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $fullName ?: ($user->name ?? 'Unknown') }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $jobType }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            CA$ {{ $hourlyRate }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ number_format((float)$hours, 2) }}
                        </td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            <button
                                type="button"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                data-user-id="{{ $user->id ?? '' }}"

                                data-start="{{ $selStart }}"

                                data-end="{{ $selEnd }}">
                                Create
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No employees with hours in this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($globalPayslips, 'links'))
            <div class="mt-4">
                {{ $globalPayslips->links() }}
            </div>
        @endif
    </div>
</section>

<div id="payslipModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-labelledby="payslipModalTitle" role="dialog" aria-modal="true">
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

            <form action="{{ route('payslip.store') }}" method="POST" class="space-y-6 mt-4">
                @csrf

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="employee_name" :value="__('Employee')" />
                        <x-text-input id="employee_name" name="employee_name" type="text" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('employee_name') }}" disabled />
                        <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="job_type_display" :value="__('Job Type')" />
                        <x-text-input id="job_type_display" name="job_type_display" type="text" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('job_type') }}" disabled />
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="hours_worked" :value="__('Hours Worked')" />
                        <x-text-input id="hours_worked" name="hours_worked" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('hours_worked', 0) }}" disabled />
                        <x-input-error class="mt-2" :messages="$errors->get('hours_worked')" />
                    </div>

                    <div class="flex-1">
                        <x-input-label for="hourly_rate" :value="__('Hourly Rate')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('hourly_rate', 0) }}" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="cash_loan_deduction" :value="__('Cash Loan Deduction')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="cash_loan_deduction" name="cash_loan_deduction" type="text" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('cash_loan_deduction', 0) }}" disabled />
                        </div>
                    </div>

                    <div class="flex-1">
                        <x-input-label for="gross_pay" :value="__('Gross Pay')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="gross_pay" name="gross_pay" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('gross_pay', 0) }}" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-4">
                    <div class="flex-1">
                        <x-input-label for="adjustments" :value="__('Adjustments')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="adjustments" name="adjustments" type="number" step="0.01" class="mt-1 block w-full pl-14" value="{{ old('adjustments', 0) }}" />
                        </div>
                    </div>

                    <div class="flex-1">
                        <x-input-label for="net_pay" :value="__('Net Pay')" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                            <x-text-input id="net_pay" name="net_pay" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('net_pay', 0) }}" disabled />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>
                        Add Payslip
                    </x-primary-button>
                </div>

                <input type="hidden" name="job_type" id="job_type_hidden" value="{{ old('job_type', '') }}">
                <input type="hidden" name="hourly_rate" id="hourly_rate_hidden" value="{{ old('hourly_rate', 0) }}">
                <input type="hidden" name="hours_worked" id="hours_worked_hidden" value="{{ old('hours_worked', 0) }}">
                <input type="hidden" name="gross_pay" id="gross_pay_hidden" value="{{ old('gross_pay', 0) }}">
                <input type="hidden" name="adjustments" id="adjustments_hidden" value="{{ old('adjustments', 0) }}">
                <input type="hidden" name="adjustments_details" id="adjustments_details_hidden" value='{{ old("adjustments_details", "[]") }}'>
                <input type="hidden" name="net_pay" id="net_pay_hidden" value="{{ old('net_pay', 0) }}">
                <!-- Add these so the modal can be populated from the table Create button -->
                <input type="hidden" name="user_id" id="user_id" value="">
                <input type="hidden" name="period_from" id="period_from" value="">
                <input type="hidden" name="period_to" id="period_to" value="">
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Existing "Add Payslip" logic
        const userIdHidden = document.getElementById('user_id');
        const periodFromHidden = document.getElementById('period_from');
        const periodToHidden = document.getElementById('period_to');

        const employeeNameInput = document.getElementById('employee_name');
        const jobTypeInput = document.getElementById('job_type_display');
        const hourlyRateInput = document.getElementById('hourly_rate');
        const hoursWorkedInput = document.getElementById('hours_worked');
        const grossPayInput = document.getElementById('gross_pay');
        const adjustmentsInput = document.getElementById('adjustments');
        const netPayInput = document.getElementById('net_pay');
        const cashLoanInput = document.getElementById('cash_loan_deduction');

        const jobTypeHidden = document.getElementById('job_type_hidden');
        const hourlyRateHidden = document.getElementById('hourly_rate_hidden');
        const hoursWorkedHidden = document.getElementById('hours_worked_hidden');
        const grossPayHidden = document.getElementById('gross_pay_hidden');
        const adjustmentsHidden = document.getElementById('adjustments_hidden');
        const netPayHidden = document.getElementById('net_pay_hidden');

        const modal = document.getElementById('payslipModal');

    function calculatePayslip() {
        const hourlyRate = parseFloat(hourlyRateInput.value) || 0;
        const hoursWorked = parseFloat(hoursWorkedInput.value) || 0;
        const adjustments = parseFloat(adjustmentsInput.value) || 0;

        const grossPay = hourlyRate * hoursWorked;
        const netPay = grossPay + adjustments;

        grossPayInput.value = grossPay.toFixed(2);
        netPayInput.value = netPay.toFixed(2);

        grossPayHidden.value = grossPay.toFixed(2);
        netPayHidden.value = netPay.toFixed(2);
    }

        async function fetchHoursAndGross() {
            const userId = userIdHidden.value;
            const periodFrom = periodFromHidden.value;
            const periodTo = periodToHidden.value;
            if (!userId || !periodFrom || !periodTo) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const body = new URLSearchParams();
                body.append('user_id', userId);
                body.append('period_from', periodFrom);
                body.append('period_to', periodTo);

                const resp = await fetch('/payslip/calc-hours', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}),
                        'Accept': 'application/json'
                    },
                    body: body.toString()
                });

                if (!resp.ok) return;

            const data = await resp.json();

            const hours = parseFloat(data.hours) || 0;
            const gross = parseFloat(data.gross) || 0;

            hoursWorkedInput.value = hours.toFixed(2);
            hoursWorkedHidden.value = hours.toFixed(2);

            grossPayInput.value = gross.toFixed(2);
            grossPayHidden.value = gross.toFixed(2);

                calculatePayslip();
            } catch {}
        }

        // Populate modal when pressing "Create"
        document.querySelectorAll('#ps_rows button[data-user-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                const tr = btn.closest('tr');
                const userId = btn.getAttribute('data-user-id') || '';
                const start = btn.getAttribute('data-start') || '';
                const end = btn.getAttribute('data-end') || '';

                const tds = tr?.querySelectorAll('td') || [];
                const employeeName = (tds[0]?.textContent || '').trim();
                const jobType = (tds[1]?.textContent || '').trim();
                const rateText = (tds[2]?.textContent || '').replace(/[^\d.-]/g, '');
                const hoursText = (tds[3]?.textContent || '').replace(/[^\d.-]/g, '');

                const rate = parseFloat(rateText) || 0;
                const hours = parseFloat(hoursText) || 0;

                // IDs and period
                userIdHidden.value = userId;
                periodFromHidden.value = start;
                periodToHidden.value = end;

                // Visible fields
                employeeNameInput.value = employeeName;   // fix: correct employee name
                jobTypeInput.value = jobType;
                hourlyRateInput.value = rate.toFixed(2);
                hoursWorkedInput.value = hours.toFixed(2);

                // Hidden mirrors
                jobTypeHidden.value = jobType;
                hourlyRateHidden.value = rate.toFixed(2);
                hoursWorkedHidden.value = hours.toFixed(2);

                // Ensure adjustments populated
                const adj = adjustmentsInput.value?.trim();
                const adjValue = adj === '' ? '0.00' : (parseFloat(adj) || 0).toFixed(2);
                adjustmentsInput.value = adjValue;
                adjustmentsHidden.value = adjValue;

                // Ensure cash loan deduction shows 0 when missing
                if (!cashLoanInput.value || cashLoanInput.value.trim() === '') {
                    cashLoanInput.value = '0';
                }

                calculatePayslip();
                fetchHoursAndGross();

                modal?.classList.remove('hidden');
            });
        });

        // Keep existing listeners
        adjustmentsInput?.addEventListener('input', function () {
            adjustmentsHidden.value = adjustmentsInput.value;
            calculatePayslip();
        });

    hoursWorkedInput?.addEventListener('input', function () {
        hoursWorkedHidden.value = hoursWorkedInput.value;
        calculatePayslip();
    });

        // Close button
        document.getElementById('payslipModalClose')?.addEventListener('click', () => {
            modal?.classList.add('hidden');
        });

        // Optional: close when clicking backdrop
        modal?.querySelector('.absolute.inset-0')?.addEventListener('click', () => {
            modal?.classList.add('hidden');
        });

        // The old ps_month/ps_year/ps_half logic can remain; it’s guarded by null checks.
    });
</script>
