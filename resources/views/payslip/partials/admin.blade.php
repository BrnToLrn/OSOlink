<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Add Payslip
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Issue a payslip for an employee.
            </p>
        </div>
    </header>

    <form action="{{ route('payslip.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="flex items-center gap-4 mt-4">
            <!-- Select Employee -->
            <div class="flex-1">
                <x-input-label for="user_id" :value="__('Employee')" />
                <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Employee</option>
                    @if(!empty($users) && $users->count())
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" data-job="{{ $user->job_type }}" data-rate="{{ $user->hourly_rate ?? 0 }}" data-hours="{{ $user->hours_worked ?? 0 }}">
                                {{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>No employees available</option>
                    @endif
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Period From -->
            <div class="flex-1">
                <x-input-label for="period_from" :value="__('Period From')" />
                <x-text-input id="period_from" name="period_from" type="date" class="mt-1 block w-full" value="{{ old('period_from', now()->subDays(15)->toDateString()) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('period_from')" />
            </div>

            <!-- Period To -->
            <div class="flex-1">
                <x-input-label for="period_to" :value="__('Period To')" />
                <x-text-input id="period_to" name="period_to" type="date" class="mt-1 block w-full" value="{{ old('period_to', now()->toDateString()) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('period_to')" />
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Job Type -->
            <div class="flex-1">
                <x-input-label for="job_type" :value="__('Job Type')" />
                <x-text-input id="job_type" name="job_type" type="text" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('job_type') }}" disabled />
            </div>

            <!-- Hourly Rate -->
            <div class="flex-1">
                <x-input-label for="hourly_rate" :value="__('Hourly Rate')" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                    <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('hourly_rate', 0) }}" disabled />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Hours Worked -->
            <div class="flex-1">
                <x-input-label for="hours_worked" :value="__('Hours Worked')" />
                <x-text-input id="hours_worked" name="hours_worked" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('hours_worked', 0) }}" disabled />
                <x-input-error class="mt-2" :messages="$errors->get('hours_worked')" />
            </div>

            <!-- Gross Pay -->
            <div class="flex-1">
                <x-input-label for="gross_pay" :value="__('Gross Pay')" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                    <x-text-input id="gross_pay" name="gross_pay" type="number" step="0.01" min="0" class="mt-1 block w-full pl-14 cursor-not-allowed opacity-75" value="{{ old('gross_pay', 0) }}" disabled />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Adjustments -->
            <div class="flex-1">
                <x-input-label for="adjustments" :value="__('Adjustments')" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300 pointer-events-none">CA$</span>
                    <x-text-input id="adjustments" name="adjustments" type="number" step="0.01" class="mt-1 block w-full pl-14" value="{{ old('adjustments', 0) }}" />
                </div>
            </div>

            <!-- Net Pay -->
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

            @if(session('success'))
                <p class="text-sm text-green-600 dark:text-green-400">
                    {{ session('success') }}
                </p>
            @endif
        </div>

        <!-- Hidden fields to ensure values are submitted (disabled inputs don't submit) -->
        <input type="hidden" name="job_type" id="job_type_hidden" value="{{ old('job_type', '') }}">
        <input type="hidden" name="hourly_rate" id="hourly_rate_hidden" value="{{ old('hourly_rate', 0) }}">
        <input type="hidden" name="hours_worked" id="hours_worked_hidden" value="{{ old('hours_worked', 0) }}">
        <input type="hidden" name="gross_pay" id="gross_pay_hidden" value="{{ old('gross_pay', 0) }}">
        <input type="hidden" name="adjustments" id="adjustments_hidden" value="{{ old('adjustments', 0) }}">
        <input type="hidden" name="adjustments_details" id="adjustments_details_hidden" value='{{ old("adjustments_details", "[]") }}'>
        <input type="hidden" name="net_pay" id="net_pay_hidden" value="{{ old('net_pay', 0) }}">
    </form>
</section>

@php
    // Use provided collections if available
    $globalPayslips = $allPayslips ?? $payslips ?? collect();
    $monthNow = (int) now()->format('n');
    $yearNow = (int) now()->format('Y');
    $defaultHalf = (int) (now()->format('j') <= 15 ? 1 : 2);
@endphp

@if(auth()->user()?->is_admin)
<section class="mt-10">
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Payslips (Edit/Delete)
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Filter by half-month period. Periods are fixed to 1–15 and 16–30/31.
            </p>
        </div>
    </header>

    <div class="mt-4 flex flex-wrap items-end gap-4">
        <div>
            <x-input-label for="ps_month" value="Month" />
            <select id="ps_month" class="mt-1 w-44 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ $m === $monthNow ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <x-input-label for="ps_year" value="Year" />
            <select id="ps_year" class="mt-1 w-28 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                @for($y = $yearNow-1; $y <= $yearNow+1; $y++)
                    <option value="{{ $y }}" {{ $y === $yearNow ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <div class="flex flex-col">
            <span class="text-sm text-gray-700 dark:text-gray-300">Half</span>
            <div class="mt-2 flex items-center gap-4">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="ps_half" id="ps_half_1" value="1" {{ $defaultHalf === 1 ? 'checked' : '' }}>
                    <span class="text-sm">1–15</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="ps_half" id="ps_half_2" value="2" {{ $defaultHalf === 2 ? 'checked' : '' }}>
                    <span class="text-sm">16–30/31</span>
                </label>
            </div>
        </div>

        <div class="ml-auto">
            <x-primary-button id="ps_apply_btn">Apply</x-primary-button>
            <span id="ps_range_hint" class="ml-3 text-sm text-gray-600 dark:text-gray-400"></span>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm rounded-lg overflow-hidden divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-3 py-2 text-left">Employee</th>
                    <th class="px-3 py-2 text-left">Issue Date</th>
                    <th class="px-3 py-2 text-left">Period</th>
                    <th class="px-3 py-2 text-right">Gross</th>
                    <th class="px-3 py-2 text-right">Net</th>
                    <th class="px-3 py-2 text-center">Status</th>
                    <th class="px-3 py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="ps_rows" class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($globalPayslips as $p)
                    @php
                        $pf = $p->period_from instanceof \Carbon\Carbon ? $p->period_from->format('Y-m-d') : \Carbon\Carbon::parse($p->period_from)->format('Y-m-d');
                        $pt = $p->period_to instanceof \Carbon\Carbon ? $p->period_to->format('Y-m-d') : \Carbon\Carbon::parse($p->period_to)->format('Y-m-d');
                        $issued = $p->issue_date instanceof \Carbon\Carbon ? $p->issue_date->format('Y-m-d') : \Carbon\Carbon::parse($p->issue_date)->format('Y-m-d');
                    @endphp
                    <tr class="ps-row"
                        data-period-from="{{ $pf }}"
                        data-period-to="{{ $pt }}"
                        data-issue-date="{{ $issued }}">
                        <td class="px-3 py-2">
                            {{ $p->user?->first_name }} {{ $p->user?->last_name }}
                        </td>
                        <td class="px-3 py-2">
                            {{ \Carbon\Carbon::parse($issued)->format('M d, Y') }}
                        </td>
                        <td class="px-3 py-2">
                            {{ \Carbon\Carbon::parse($pf)->format('M d') }} – {{ \Carbon\Carbon::parse($pt)->format('M d, Y') }}
                        </td>
                        <td class="px-3 py-2 text-right">CA$ {{ number_format($p->gross_pay,2) }}</td>
                        <td class="px-3 py-2 text-right">CA$ {{ number_format($p->net_pay,2) }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $p->is_paid ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                               : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                                {{ $p->is_paid ? 'Paid' : 'Unpaid' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('payslip.show', $p) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                @if(!$p->is_paid)
                                    <a href="{{ route('payslip.edit', $p) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                    <form action="{{ route('payslip.destroy', $p) }}" method="POST" class="inline m-0" onsubmit="return confirm('Delete this payslip?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No payslips available to manage.
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
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Existing "Add Payslip" logic
        const userSelect = document.getElementById('user_id');
        const jobTypeInput = document.getElementById('job_type');
        const hourlyRateInput = document.getElementById('hourly_rate');
        const hoursWorkedInput = document.getElementById('hours_worked');
        const grossPayInput = document.getElementById('gross_pay');
        const adjustmentsInput = document.getElementById('adjustments');
        const netPayInput = document.getElementById('net_pay');

        const jobTypeHidden = document.getElementById('job_type_hidden');
        const hourlyRateHidden = document.getElementById('hourly_rate_hidden');
        const hoursWorkedHidden = document.getElementById('hours_worked_hidden');
        const grossPayHidden = document.getElementById('gross_pay_hidden');
        const adjustmentsHidden = document.getElementById('adjustments_hidden');
        const netPayHidden = document.getElementById('net_pay_hidden');

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
            const userId = userSelect.value;
            const periodFrom = document.getElementById('period_from').value;
            const periodTo = document.getElementById('period_to').value;
            if (!userId || !periodFrom || !periodTo) return;

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;

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

                if (!resp.ok) {
                    console.error('Failed to fetch hours:', resp.statusText);
                    return;
                }

                const data = await resp.json();
                const hours = parseFloat(data.hours) || 0;
                const gross = parseFloat(data.gross) || 0;

                hoursWorkedInput.value = hours.toFixed(2);
                hoursWorkedHidden.value = hours.toFixed(2);

                grossPayInput.value = gross.toFixed(2);
                grossPayHidden.value = gross.toFixed(2);

                const selectedOption = userSelect.options[userSelect.selectedIndex];
                const optRate = parseFloat(selectedOption?.getAttribute('data-rate')) || null;
                if (optRate !== null) {
                    hourlyRateInput.value = optRate.toFixed(2);
                    hourlyRateHidden.value = optRate.toFixed(2);
                }

                calculatePayslip();
            } catch (e) {
                console.error('Error fetching hours:', e);
            }
        }

        userSelect?.addEventListener('change', function () {
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            const jobType = selectedOption.getAttribute('data-job') || '';
            const hourlyRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            const hoursWorked = parseFloat(selectedOption.getAttribute('data-hours')) || 0;

            jobTypeInput.value = jobType;
            hourlyRateInput.value = hourlyRate.toFixed(2);

            hoursWorkedInput.value = hoursWorked.toFixed(2);
            hoursWorkedHidden.value = hoursWorked.toFixed(2);

            jobTypeHidden.value = jobType;
            hourlyRateHidden.value = hourlyRate.toFixed(2);

            fetchHoursAndGross();
        });

        const periodFromInput = document.getElementById('period_from');
        const periodToInput = document.getElementById('period_to');

        (function setDefaultPeriodDates() {
            const today = new Date();
            const plus15 = new Date(today);
            plus15.setDate(today.getDate() + 15);
            const fmt = d => d.toISOString().slice(0,10);

            if (periodFromInput && !periodFromInput.value) periodFromInput.value = fmt(today);
            if (periodToInput && !periodToInput.value) periodToInput.value = fmt(plus15);

            periodFromInput?.dispatchEvent(new Event('change', { bubbles: true }));
            periodToInput?.dispatchEvent(new Event('change', { bubbles: true }));
        })();

        [periodFromInput, periodToInput].forEach(el => {
            el?.addEventListener('change', function () {
                fetchHoursAndGross();
            });
        });

        if (userSelect?.value) {
            userSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        hoursWorkedInput?.addEventListener('input', function () {
            hoursWorkedHidden.value = hoursWorkedInput.value;
            calculatePayslip();
        });

        adjustmentsInput?.addEventListener('input', function () {
            adjustmentsHidden.value = adjustmentsInput.value;
            calculatePayslip();
        });

        // ========== Manage Payslips filter (fixed half-months) ==========
        const psMonth = document.getElementById('ps_month');
        const psYear = document.getElementById('ps_year');
        const psHalf1 = document.getElementById('ps_half_1');
        const psHalf2 = document.getElementById('ps_half_2');
        const psApply = document.getElementById('ps_apply_btn');
        const psRows = document.getElementById('ps_rows');
        const psHint = document.getElementById('ps_range_hint');

        function pad(n){ return n < 10 ? '0' + n : '' + n; }

        function lastDayOfMonth(year, month) {
            return new Date(year, month, 0).getDate(); // month is 1-based in our UI, 0-based in Date
        }

        function rangeForHalf(year, month, half) {
            const startDay = half === 1 ? 1 : 16;
            const endDay = half === 1 ? 15 : lastDayOfMonth(year, month);
            const start = `${year}-${pad(month)}-${pad(startDay)}`;
            const end = `${year}-${pad(month)}-${pad(endDay)}`;
            return { start, end, startDay, endDay };
        }

        function overlaps(aStart, aEnd, bStart, bEnd) {
            return aStart <= bEnd && bStart <= aEnd;
        }

        function filterRows() {
            if (!psRows) return;
            const year = parseInt(psYear.value, 10);
            const month = parseInt(psMonth.value, 10);
            const half = psHalf2?.checked ? 2 : 1;
            const { start, end, startDay, endDay } = rangeForHalf(year, month, half);

            if (psHint) {
                const monthName = new Date(`${year}-${pad(month)}-01T00:00:00`).toLocaleString(undefined, { month: 'long' });
                psHint.textContent = `Showing ${startDay}–${endDay} ${monthName} ${year}`;
            }

            const s = start;
            const e = end;

            psRows.querySelectorAll('tr.ps-row').forEach(tr => {
                const pf = tr.getAttribute('data-period-from');
                const pt = tr.getAttribute('data-period-to');

                // Show if the payslip period overlaps the selected half-month range
                const show = overlaps(pf, pt, s, e);
                tr.style.display = show ? '' : 'none';
            });
        }

        psApply?.addEventListener('click', function (e) {
            e.preventDefault();
            filterRows();
        });

        if (psRows) {
            filterRows();
        }
    });
</script>