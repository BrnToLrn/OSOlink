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
                            <option value="{{ $user->id }}" data-job="{{ $user->job_type }}" data-rate="{{ $user->hourly_rate ?? 0 }}">
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
                <x-text-input id="period_from" name="period_from" type="date" class="mt-1 block w-full" value="{{ old('period_from') }}" />
                <x-input-error class="mt-2" :messages="$errors->get('period_from')" />
            </div>

            <!-- Period To -->
            <div class="flex-1">
                <x-input-label for="period_to" :value="__('Period To')" />
                <x-text-input id="period_to" name="period_to" type="date" class="mt-1 block w-full" value="{{ old('period_to') }}" />
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
                <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('hourly_rate', 0) }}" disabled />
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Hours Worked -->
            <div class="flex-1">
                <x-input-label for="hours_worked" :value="__('Hours Worked')" />
                <x-text-input id="hours_worked" name="hours_worked" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('hours_worked', 0) }}" disabled />
            </div>

            <!-- Gross Pay -->
            <div class="flex-1">
                <x-input-label for="gross_pay" :value="__('Gross Pay')" />
                <x-text-input id="gross_pay" name="gross_pay" type="number" step="0.01" min="0" class="mt-1 block w-full cursor-not-allowed opacity-75" value="{{ old('gross_pay', 0) }}" disabled />
            </div>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <!-- Deductions -->
            <div class="flex-1">
                <x-input-label for="deductions" :value="__('Deductions')" />
                <x-text-input id="deductions" name="deductions" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('deductions', 0) }}" />
            </div>
        </div>

        <x-primary-button>
            Add Payslip
        </x-primary-button>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const userSelect = document.getElementById('user_id');
    const fromInput = document.getElementById('period_from');
    const toInput = document.getElementById('period_to');
    const hoursInput = document.getElementById('hours_worked');
    const rateInput = document.getElementById('hourly_rate');
    const grossInput = document.getElementById('gross_pay');
    const jobInput = document.getElementById('job_type');

    // populate disabled fields from selected user option, then request calc
    function populateFromOption() {
        const opt = userSelect?.selectedOptions?.[0];
        if (!opt) return;
        jobInput.value = opt.dataset.job ?? '';
        // set rate (disabled) — keep it numeric string
        rateInput.value = (opt.dataset.rate ?? 0);
    }

    async function recalcFromServer() {
        if (!userSelect?.value || !fromInput?.value || !toInput?.value) return;

        const url = "{{ route('payslip.calculateHours') }}";
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const body = new URLSearchParams();
        body.append('user_id', userSelect.value);
        body.append('period_from', fromInput.value);
        body.append('period_to', toInput.value);
        body.append('hourly_rate', rateInput.value || 0);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: body.toString()
            });
            if (!res.ok) return;
            const json = await res.json();
            // update disabled inputs (value property works even if disabled)
            hoursInput.value = json.hours;
            grossInput.value = json.gross.toFixed(2);
        } catch (e) {
            console.error('calc error', e);
        }
    }

    // when user changes, populate rate/job and then calc
    userSelect?.addEventListener('change', function () {
        populateFromOption();
        recalcFromServer();
    });

    // when date or rate changes, recalc
    fromInput?.addEventListener('change', recalcFromServer);
    toInput?.addEventListener('change', recalcFromServer);
    rateInput?.addEventListener('input', recalcFromServer);
});
</script>