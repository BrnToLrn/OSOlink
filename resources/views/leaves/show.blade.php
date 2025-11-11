<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        View Leave
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Show specific leave details.
                    </p>
                </header>

                <div class="mt-6 space-y-6">
                    <div class="flex items-center gap-4 mt-4">
                        <!-- Start Date -->
                        <div class="flex-1">
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <x-text-input
                                id="start_date"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('start_date', $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('F j, Y') : '') }}"
                                disabled
                            />
                        </div>

                        <!-- End Date -->
                        <div class="flex-1">
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <x-text-input
                                id="end_date"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('end_date', $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('F j, Y') : '') }}"
                                disabled
                            />
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <x-input-label for="reason" :value="__('Reason of Leave')" />
                        <x-text-input
                            id="reason"
                            type="text"
                            class="mt-1 block w-full cursor-not-allowed opacity-75"
                            value="{{ old('reason', $leave->reason ?? '') }}"
                            disabled
                        />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Type -->
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Leave')" />
                            <x-text-input
                                id="type"
                                type="text"
                                class="mt-1 block w-full cursor-not-allowed opacity-75"
                                value="{{ old('type', $leave->type ?? '') }}"
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
                                value="{{ old('status', $leave->status ?? '') }}"
                                disabled
                            />
                        </div>
                    </div>

                    <form method="GET" action="{{ route('leaves.index') }}">
                        <x-primary-button>Back to leaves</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>