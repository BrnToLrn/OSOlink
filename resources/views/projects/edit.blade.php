<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Edit Project
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Edit an existing project for employees to collaborate on.
                        </p>
                </header>
                <form method="POST" action="{{ route('projects.update', $project->id) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" value="{{ old('name', $project->name) }}" class="mt-1 block w-full" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Description -->
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-text-input id="description" name="description" type="text" value="{{ old('description', $project->description) }}" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <!-- Status -->
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            required>
                            <option value="Not Started" {{ old('status', $project->status) == 'Not Started' ? 'selected' : '' }}>Not Started</option>
                            <option value="In Progress" {{ old('status', $project->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="On Hold" {{ old('status', $project->status) == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="Completed" {{ old('status', $project->status) == 'Completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <!-- Start & End Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_date" :value="__('Start Date')" />
                            <input 
                                type="date" 
                                id="start_date"
                                name="start_date" 
                                value="{{ old('start_date') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 
                                    focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 
                                    dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                        </div>

                        <div>
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <input 
                                type="date" 
                                id="end_date"
                                name="end_date" 
                                value="{{ old('end_date') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 
                                    focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 
                                    dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                        </div>
                    </div>

                    @push('scripts')
                    <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const startInput = document.getElementById('start_date');
                        const endInput = document.getElementById('end_date');

                        if (!startInput || !endInput) return;

                        const today = new Date().toISOString().split('T')[0];

                        // Initialize start date if empty
                        if (!startInput.value) startInput.value = today;
                        startInput.min = today;

                        // Helper to add days to a date string
                        function addDays(dateStr, days) {
                            const date = new Date(dateStr + 'T00:00:00');
                            date.setDate(date.getDate() + days);
                            return date.toISOString().split('T')[0];
                        }

                        function updateEndDate() {
                            const start = startInput.value;
                            if (!start) return;

                            // End date cannot be before start
                            endInput.min = start;

                            // If end date is empty or less than start + 7 days, auto-set it
                            const defaultEnd = addDays(start, 7);
                            if (!endInput.value || endInput.value < start || endInput.value < defaultEnd) {
                                endInput.value = defaultEnd;
                            }
                        }

                        // Initialize end date on load
                        updateEndDate();

                        // Update end date whenever start date changes
                        startInput.addEventListener('change', updateEndDate);
                    });
                    </script>
                    @endpush

                    <x-primary-button type="submit">
                        Update Project
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
