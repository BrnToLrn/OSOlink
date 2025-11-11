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

                    <!-- Date Requested & Amount (mirrors start/end date layout) -->
                    <div class="flex items-center gap-4 mt-4">
                        <div class="flex-1">
                            <x-input-label for="date_requested" :value="__('Date Requested')" />
                            <x-text-input id="date_requested" name="date_requested" type="date" class="mt-1 block w-full"
                                :value="old('date_requested')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('date_requested')" />
                        </div>

                        <div class="flex-1">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                :value="old('amount')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                        </div>
                    </div>

                    <!-- Remarks (mirrors Reason of Leave) -->
                    <div>
                        <x-input-label for="remarks" :value="__('Purpose / Remarks')" />
                        <x-text-input id="remarks" name="remarks" type="text" class="mt-1 block w-full"
                            :value="old('remarks')" />
                        <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
                    </div>

                    <!-- Type (mirrors Type of Leave) -->
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

                        <!-- Status (disabled like in leaves/create) -->
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
</x-app-layout>