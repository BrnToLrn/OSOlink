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

<script>
    document.addEventListener('DOMContentLoaded', function () {
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

        // POST to server to get hours & gross for given user + period
        async function fetchHoursAndGross() {
            const userId = userSelect.value;
            const periodFrom = document.getElementById('period_from').value;
            const periodTo = document.getElementById('period_to').value;

            // need a user and valid dates
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
                    // validation or server error - do nothing but log
                    console.error('Failed to fetch hours:', resp.statusText);
                    return;
                }

                const data = await resp.json();

                const hours = parseFloat(data.hours) || 0;
                const gross = parseFloat(data.gross) || 0;

                // populate fields + hidden fields
                hoursWorkedInput.value = hours.toFixed(2);
                hoursWorkedHidden.value = hours.toFixed(2);

                grossPayInput.value = gross.toFixed(2);
                grossPayHidden.value = gross.toFixed(2);

                // keep hourly rate in sync if user option carries it
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

        userSelect.addEventListener('change', function () {
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            const jobType = selectedOption.getAttribute('data-job') || '';
            const hourlyRate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            const hoursWorked = parseFloat(selectedOption.getAttribute('data-hours')) || 0;

            jobTypeInput.value = jobType;
            hourlyRateInput.value = hourlyRate.toFixed(2);

            // format hours for display and keep hidden in sync
            hoursWorkedInput.value = hoursWorked.toFixed(2);
            hoursWorkedHidden.value = hoursWorked.toFixed(2);

            jobTypeHidden.value = jobType;
            hourlyRateHidden.value = hourlyRate.toFixed(2);

            // fetch real hours/gross for selected period if dates present
            fetchHoursAndGross();
        });

        // re-fetch when period inputs change
        const periodFromInput = document.getElementById('period_from');
        const periodToInput = document.getElementById('period_to');

        // JS fallback: if no value, autofill period_from = today and period_to = today + 15 days
        (function setDefaultPeriodDates() {
            const today = new Date();
            const plus15 = new Date(today);
            plus15.setDate(today.getDate() + 15);
            const fmt = d => d.toISOString().slice(0,10);

            if (!periodFromInput.value) periodFromInput.value = fmt(today);
            if (!periodToInput.value) periodToInput.value = fmt(plus15);

            // update hidden/derived state and fetch hours for the selected user (if any)
            periodFromInput.dispatchEvent(new Event('change', { bubbles: true }));
            periodToInput.dispatchEvent(new Event('change', { bubbles: true }));
        })();

        [periodFromInput, periodToInput].forEach(el => {
            el.addEventListener('change', function () {
                fetchHoursAndGross();
            });
        });

        // initialize fields for the currently-selected option (if any)
        (function initSelectedUser() {
            if (userSelect.value) {
                const evt = new Event('change', { bubbles: true });
                userSelect.dispatchEvent(evt);
            }
        })();

        hoursWorkedInput.addEventListener('input', function () {
            hoursWorkedHidden.value = hoursWorkedInput.value;
            calculatePayslip();
        });

        adjustmentsInput.addEventListener('input', function () {
            adjustmentsHidden.value = adjustmentsInput.value;
            calculatePayslip();
        });
    });
</script>