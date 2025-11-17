<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Create Leave
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Make a leave request.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('leaves.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Start Date (editable) -->
                        <div class="flex-1">
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input
                                id="start_date"
                                name="start_date"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('start_date')"
                                required />
                            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                        </div>

                        <!-- End Date (must be strictly after start) -->
                        <div class="flex-1">
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <x-text-input
                                id="end_date"
                                name="end_date"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('end_date')"
                                required />
                            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <x-input-label for="reason" :value="__('Reason of Leave')" />
                        <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason')" />
                        <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    </div>

                    <div class="flex items-centered gap-4 mt-4">
                        <!-- Type -->
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Leave')" />
                            <select id="type" name="type" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300
                                       focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600
                                       rounded-md shadow-sm">
                                <option value="">Select Type</option>
                                <option value="Sick Leave" {{ old('type') == 'Sick Leave' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="Vacation Leave" {{ old('type') == 'Vacation Leave' ? 'selected' : '' }}>Vacation Leave</option>
                                <option value="Bereavement Leave" {{ old('type') == 'Bereavement Leave' ? 'selected' : '' }}>Bereavement Leave</option>
                                <option value="Emergency/Personal Leave" {{ old('type') == 'Emergency/Personal Leave' ? 'selected' : '' }}>Emergency/Personal Leave</option>
                                <option value="Mandatory Leave" {{ old('type') == 'Mandatory Leave' ? 'selected' : '' }}>Mandatory Leave</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <!-- Status -->
                        <div class="flex-1">
                            <x-input-label for="status" :value="__('Status')" />
                            <x-text-input id="status" name="status" type="text"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-70"
                                :value="__('Pending')" disabled />
                            <input type="hidden" name="status" value="Pending" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Create</x-primary-button>
                        @if (session('create_success'))
                            <p class="text-sm text-green-600 dark:text-green-400">{{ session('create_success') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Default start to local today (editable); end must be > start; roll default at local midnight if untouched) -->
    <script>
        (function () {
            const start = document.getElementById('start_date');
            const end = document.getElementById('end_date');
            if (!start || !end) return;

            const pad = n => String(n).padStart(2,'0');
            const ymd = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const addDays = (d, days) => { const x = new Date(d); x.setDate(x.getDate() + days); return x; };

            let userTouchedStart = false;

            function ensureStartDefaultToday() {
                if (!start.value) start.value = ymd(new Date());
                // autofill end to start + 1 day if end is empty
                try {
                    const s = new Date(start.value + 'T00:00:00');
                    const defaultEnd = ymd(addDays(s, 1));
                    if (!end.value) end.value = defaultEnd;
                } catch(e) {
                    // noop
                }
            }

            function syncEndMin() {
                if (!start.value) return;
                const s = new Date(start.value + 'T00:00:00');
                const minEnd = ymd(addDays(s, 1)); // strictly after start
                end.min = minEnd;
                // if end is empty or earlier than minEnd, set it to minEnd
                if (!end.value || end.value < minEnd) end.value = minEnd;
            }

            function scheduleMidnightRollover() {
                const now = new Date();
                const next = new Date(now);
                next.setHours(24, 0, 1, 0); // 1s after local midnight
                setTimeout(() => {
                    if (!userTouchedStart) {
                        start.value = ymd(new Date());
                        syncEndMin();
                    }
                    scheduleMidnightRollover();
                }, next - now);
            }

            // Init
            ensureStartDefaultToday();
            syncEndMin();
            scheduleMidnightRollover();

            start.addEventListener('change', () => { userTouchedStart = true; syncEndMin(); });
            start.addEventListener('input', () => { userTouchedStart = true; });
        })();
    </script>
</x-app-layout>