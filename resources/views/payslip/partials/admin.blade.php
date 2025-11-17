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
                                data-end="{{ $selEnd }}"
                                data-user-name="{{ $fullName ?: ($user->name ?? 'Unknown') }}"
                                data-job-type="{{ $jobType }}"
                                data-hourly-rate="{{ $hourlyRate }}"
                                data-hours="{{ number_format((float)$hours, 2, '.', '') }}"
                            >
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
    <div class="absolute inset-0 bg-black/50" id="payslipModalBackdrop" aria-hidden="true"></div>
    <div class="relative z-10 w-full max-w-3xl px-4">
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
                <input type="hidden" name="issue_date" id="issue_date" value="{{ now()->toDateString() }}">
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var psRows = document.getElementById('ps_rows');
    var modal = document.getElementById('payslipModal');
    if (!modal) return;

    // Scope queries to this modal to avoid ID collisions
    var modalBackdrop = modal.querySelector('#payslipModalBackdrop');
    var modalClose = modal.querySelector('#payslipModalClose');

    // Visible inputs (scoped)
    var employeeNameInput = modal.querySelector('#employee_name');
    var jobTypeInput      = modal.querySelector('#job_type_display');
    var hourlyRateInput   = modal.querySelector('#hourly_rate');
    var hoursWorkedInput  = modal.querySelector('#hours_worked');
    var grossPayInput     = modal.querySelector('#gross_pay');
    var adjustmentsInput  = modal.querySelector('#adjustments');
    var netPayInput       = modal.querySelector('#net_pay');
    var cashLoanInput     = modal.querySelector('#cash_loan_deduction');

    // Hidden payload (scoped)
    var userIdHidden      = modal.querySelector('#user_id');
    var periodFromHidden  = modal.querySelector('#period_from');
    var periodToHidden    = modal.querySelector('#period_to');
    var jobTypeHidden     = modal.querySelector('#job_type_hidden');
    var hourlyRateHidden  = modal.querySelector('#hourly_rate_hidden');
    var hoursWorkedHidden = modal.querySelector('#hours_worked_hidden');
    var grossPayHidden    = modal.querySelector('#gross_pay_hidden');
    var adjustmentsHidden = modal.querySelector('#adjustments_hidden');
    var netPayHidden      = modal.querySelector('#net_pay_hidden');

    function calculatePayslip() {
        var rate = parseFloat(hourlyRateInput.value) || 0;
        var hours = parseFloat(hoursWorkedInput.value) || 0;
        var adj = parseFloat(adjustmentsInput.value) || 0;

        var gross = rate * hours;
        var net = gross + adj;

        grossPayInput.value  = gross.toFixed(2);
        netPayInput.value    = net.toFixed(2);
        grossPayHidden.value = gross.toFixed(2);
        netPayHidden.value   = net.toFixed(2);
        hoursWorkedHidden.value = hours.toFixed(2);
        hourlyRateHidden.value  = rate.toFixed(2);
    }

    async function fetchHoursAndGross() {
        var userId = userIdHidden.value;
        var periodFrom = periodFromHidden.value;
        var periodTo = periodToHidden.value;
        if (!userId || !periodFrom || !periodTo) return;

        var csrfEl = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfEl ? csrfEl.getAttribute('content') : null;

        try {
            var body = new URLSearchParams();
            body.append('user_id', userId);
            body.append('period_from', periodFrom);
            body.append('period_to', periodTo);

            var resp = await fetch('/payslip/calc-hours', {
                method: 'POST',
                headers: Object.assign({
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json'
                }, csrf ? {'X-CSRF-TOKEN': csrf} : {}),
                body: body.toString()
            });

            if (!resp.ok) return;

            var data = await resp.json();
            var hours = parseFloat(data.hours) || 0;
            var gross = parseFloat(data.gross) || 0;

            hoursWorkedInput.value  = hours.toFixed(2);
            hoursWorkedHidden.value = hours.toFixed(2);
            grossPayInput.value     = gross.toFixed(2);
            grossPayHidden.value    = gross.toFixed(2);

            calculatePayslip();
        } catch {}
    }

    // Delegated click for Create buttons
    if (psRows) {
        psRows.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-user-id]');
            if (!btn) return;

            var userId      = btn.dataset.userId || '';
            var start       = btn.dataset.start || '';
            var end         = btn.dataset.end || '';
            var employee    = btn.dataset.userName || '';
            var jobType     = btn.dataset.jobType || '';
            var rate        = parseFloat((btn.dataset.hourlyRate || '0').replace(/,/g, '')) || 0;
            var hours       = parseFloat((btn.dataset.hours || '0').replace(/,/g, '')) || 0;

            // Fill hidden identifiers
            userIdHidden.value     = userId;
            periodFromHidden.value = start;
            periodToHidden.value   = end;

            // Fill visible fields
            employeeNameInput.value = employee;
            jobTypeInput.value      = jobType;
            hourlyRateInput.value   = rate.toFixed(2);
            hoursWorkedInput.value  = hours.toFixed(2);

            // Mirrors
            jobTypeHidden.value      = jobType;
            hourlyRateHidden.value   = rate.toFixed(2);
            hoursWorkedHidden.value  = hours.toFixed(2);

            // Defaults
            if (!cashLoanInput.value || cashLoanInput.value.trim() === '') cashLoanInput.value = '0.00';
            var adjVal = adjustmentsInput.value && adjustmentsInput.value.trim() !== ''
                ? (parseFloat(adjustmentsInput.value) || 0).toFixed(2)
                : '0.00';
            adjustmentsInput.value  = adjVal;
            adjustmentsHidden.value = adjVal;

            calculatePayslip();
            fetchHoursAndGross();

            modal.classList.remove('hidden');
        });
    }

    // Live updates
    if (adjustmentsInput) {
        adjustmentsInput.addEventListener('input', function () {
            adjustmentsHidden.value = adjustmentsInput.value;
            calculatePayslip();
        });
    }
    if (hoursWorkedInput) {
        hoursWorkedInput.addEventListener('input', function () {
            hoursWorkedHidden.value = hoursWorkedInput.value;
            calculatePayslip();
        });
    }

    // Close handlers (scoped)
    if (modalClose)  modalClose.addEventListener('click', function () { modal.classList.add('hidden'); });
    if (modalBackdrop) modalBackdrop.addEventListener('click', function () { modal.classList.add('hidden'); });
});
</script>