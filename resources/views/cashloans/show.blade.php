<x-app-layout>
    @php($loan = $loan ?? ($cashloan ?? null))
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        View Cash Loan
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Show specific cash loan details.
                    </p>
                </header>

                <div class="mt-6 space-y-6">
                    <div class="flex items-center gap-4 mt-4">
                        <!-- Date Requested -->
                        <div class="flex-1">
                            <x-input-label for="date_requested" :value="__('Date Requested')" />
                            <x-text-input
                                id="date_requested"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('date_requested', $loan?->date_requested ? \Carbon\Carbon::parse($loan->date_requested)->format('F j, Y') : '') }}"
                                disabled
                            />
                        </div>

                        <!-- Amount -->
                        <div class="flex-1">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <x-text-input
                                id="amount"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('amount', isset($loan?->amount) ? number_format((float)$loan->amount, 2) : '') }}"
                                disabled
                            />
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <x-input-label for="remarks" :value="__('Purpose / Remarks')" />
                        <x-text-input
                            id="remarks"
                            type="text"
                            class="mt-1 block w-full cursor-not-allowed opacity-75"
                            value="{{ old('remarks', $loan->remarks ?? '') }}"
                            disabled
                        />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Type -->
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Loan')" />
                            <x-text-input
                                id="type"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('type', $loan->type ?? '') }}"
                                disabled
                            />
                        </div>

                        <!-- Status -->
                        <div class="flex-1">
                            <x-input-label for="status" :value="__('Status')" />
                            <x-text-input
                                id="status"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('status', $loan->status ?? '') }}"
                                disabled
                            />
                        </div>
                    </div>

                    <form method="GET" action="{{ route('cashloans.index') }}">
                        <x-primary-button>Back to cash loans</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>