<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Edit Cash Loan
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Change a cash loan request.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('cashloans.update', $cashloan) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Preserve fields required by controller but not shown -->
                    <input type="hidden" name="user_id" value="{{ old('user_id', $cashloan->user_id) }}">
                    <input type="hidden" name="status" value="{{ old('status', $cashloan->status) }}">

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Date Requested -->
                        <div class="flex-1">
                            <x-input-label for="date_requested" :value="__('Date Requested')" />
                            <x-text-input
                                id="date_requested"
                                name="date_requested"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('date_requested', \Carbon\Carbon::parse($cashloan->date_requested)->format('Y-m-d'))" />
                            <x-input-error class="mt-2" :messages="$errors->get('date_requested')" />
                        </div>

                        <!-- Amount -->
                        <div class="flex-1">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <x-text-input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                :value="old('amount', $cashloan->amount)" />
                            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <x-input-label for="remarks" :value="__('Purpose / Remarks')" />
                        <x-text-input
                            id="remarks"
                            name="remarks"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('remarks', $cashloan->remarks)" />
                        <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Type -->
                        <div class="flex-1">
                            <x-input-label for="type" :value="__('Type of Loan')" />
                            <select id="type" name="type" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300
                                       focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600
                                       rounded-md shadow-sm">
                                <option value="">Select Type</option>
                                <option value="Emergency" {{ old('type', $cashloan->type) == 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                <option value="Personal" {{ old('type', $cashloan->type) == 'Personal' ? 'selected' : '' }}>Personal</option>
                                <option value="Medical" {{ old('type', $cashloan->type) == 'Medical' ? 'selected' : '' }}>Medical</option>
                                <option value="Education" {{ old('type', $cashloan->type) == 'Education' ? 'selected' : '' }}>Education</option>
                                <option value="Other" {{ old('type', $cashloan->type) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <!-- Status (display only, matches leaves/edit style) -->
                        <div class="flex-1">
                            <x-input-label for="status_display" :value="__('Status')" />
                            <x-text-input id="status_display" type="text"
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-70"
                                :value="old('status', $cashloan->status ?? 'Pending')" disabled />
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
</x-app-layout>