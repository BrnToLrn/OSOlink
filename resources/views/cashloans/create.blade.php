<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Create Cash Loan
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Make a cash loan request.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('cashloans.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <!-- Date Requested & Amount -->
                    <div class="flex items-center gap-4 mt-4">
                        <div class="flex-1">
                            <x-input-label for="date_requested" :value="__('Date Requested')" />
                            <x-text-input id="date_requested" name="date_requested" type="date"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed opacity-70"
                                :value="old('date_requested', now()->toDateString())"
                                readonly required />
                            <x-input-error class="mt-2" :messages="$errors->get('date_requested')" />
                        </div>

                        <div class="flex-1">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">CA$</span>
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full pl-14"
                                    :value="old('amount')" required />
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <x-input-label for="remarks" :value="__('Purpose / Remarks')" />
                        <x-text-input id="remarks" name="remarks" type="text" class="mt-1 block w-full"
                            :value="old('remarks')" />
                        <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
                    </div>

                    <!-- Type & Status -->
                    <div class="flex items-center gap-4 mt-4">
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Loan')" />
                            <select id="type" name="type" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300
                                       focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600
                                       rounded-md shadow-sm">
                                <option value="">Select Type</option>
                                @foreach(['Emergency','Personal','Medical','Education','Other'] as $t)
                                    <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

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
                        @if (session('success'))
                            <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Optional: refresh visible date after midnight if the tab stays open -->
    <script>
        (function () {
            const dateInput = document.getElementById('date_requested');
            if (!dateInput) return;

            function setToday() {
                const d = new Date();
                const v = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                if (dateInput.value !== v) dateInput.value = v;
            }
            setToday();
            const now = new Date();
            const nextMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate()+1);
            setTimeout(() => setToday(), nextMidnight - now);
        })();
    </script>
</x-app-layout>