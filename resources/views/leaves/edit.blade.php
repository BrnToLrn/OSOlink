<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Edit Leave
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Change a leave request.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('leaves.update', $leave) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Start Date (editable) -->
                        <div class="flex-1">
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input
                                id="start_date"
                                name="start_date"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('start_date', \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d'))"
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
                                :value="old('end_date', \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d'))"
                                required />
                            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <x-input-label for="reason" :value="__('Reason of Leave')" />
                        <x-text-input
                            id="reason"
                            name="reason"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('reason', $leave->reason)" />
                        <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Type -->
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Leave')" />
                            <select id="type" name="type" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300
                                       focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600
                                       rounded-md shadow-sm">
                                @foreach([
                                    'Sick Leave',
                                    'Vacation Leave',
                                    'Bereavement Leave',
                                    'Emergency/Personal Leave',
                                    'Mandatory Leave'
                                ] as $t)
                                    <option value="{{ $t }}" {{ old('type', $leave->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <!-- Status (display only) -->
                        <div class="flex-1">
                            <x-input-label for="status_display" :value="__('Status')" />
                            <x-text-input id="status_display" type="text"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-70"
                                :value="old('status', $leave->status ?? 'Pending')"
                                disabled />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Update</x-primary-button>
                        @if (session('update_success'))
                            <p class="text-sm text-green-600 dark:text-green-400">{{ session('update_success') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enforce: end_date strictly after start_date -->
    <script>
        (function () {
            const start = document.getElementById('start_date');
            const end = document.getElementById('end_date');
            if (!start || !end) return;

            const pad = n => String(n).padStart(2,'0');
            const ymd = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            const addDays = (d, days) => { const x = new Date(d); x.setDate(x.getDate() + days); return x; };

            function syncEndMin() {
                if (!start.value) return;
                const s = new Date(start.value + 'T00:00:00');
                const minEnd = ymd(addDays(s, 1)); // strictly after start
                end.min = minEnd;
                if (end.value && end.value < minEnd) end.value = minEnd;
            }

            syncEndMin();
            start.addEventListener('change', syncEndMin);
            start.addEventListener('input', syncEndMin);
        })();
    </script>
</x-app-layout>