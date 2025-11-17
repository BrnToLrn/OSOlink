<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Create Project
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Make a new project for employees to collaborate on.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('projects.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Description -->
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <x-text-input id="description" name="description" type="text" value="{{ old('description') }}" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <!-- Status -->
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 
                                focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 
                                rounded-md shadow-sm"
                            required>
                            <option value="Not Started" {{ old('status') == 'Not Started' ? 'selected' : '' }}>Not Started</option>
                            <option value="In Progress" {{ old('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="On Hold" {{ old('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="Completed" {{ old('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
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


                    <!-- User Assignment (Optional) -->
                    <div 
                        x-data="{
                            allUsers: {{ Js::from($users) }},
                            selectedUsers: [],
                            searchTerm: '',
                            projectLeadId: null,
                            showDropdown: false,
                            get filteredUsers() {
                                return this.allUsers.filter(user =>
                                    (user.first_name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                    user.last_name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                                    user.email.toLowerCase().includes(this.searchTerm.toLowerCase())) &&
                                    !this.selectedUsers.some(u => u.id === user.id)
                                );
                            },
                            addUser(user) {
                                this.selectedUsers.push(user);
                                this.searchTerm = '';
                                this.showDropdown = false;
                            },
                            removeUser(userId) {
                                this.selectedUsers = this.selectedUsers.filter(u => u.id !== userId);
                                if (this.projectLeadId === userId) this.projectLeadId = null;
                            },
                            isSelected(userId) {
                                return this.selectedUsers.some(u => u.id === userId);
                            }
                        }"
                        class="space-y-6"
                    >
                        <x-input-label :value="__('Assign Users')" />

                        <!-- Search Input with Dropdown -->
                        <div class="relative" @click.outside="showDropdown = false">
                            <input
                                type="text"
                                x-model="searchTerm"
                                @focus="showDropdown = true"
                                @keydown.escape="showDropdown = false"
                                placeholder="Search users by name or email..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 
                                    focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 
                                    dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            />  

                            <!-- Dropdown Results -->
                            <div
                                x-show="showDropdown"
                                x-cloak
                                class="absolute top-full left-0 right-0 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 
                                    dark:border-gray-700 rounded-md shadow-lg z-10 max-h-64 overflow-y-auto"
                            >
                                <template x-for="user in filteredUsers" :key="user.id">
                                    <button
                                        type="button"
                                        @mousedown.prevent="addUser(user)"
                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 border-b 
                                            border-gray-200 dark:border-gray-600 last:border-b-0 transition"
                                    >
                                        <div class="font-medium text-gray-900 dark:text-gray-100" 
                                            x-text="user.first_name + ' ' + (user.middle_name || '') + ' ' + user.last_name">
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="user.email"></div>
                                    </button>
                                </template>

                                <div
                                    x-show="filteredUsers.length === 0"
                                    class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm"
                                >
                                    No users found
                                </div>
                            </div>
                        </div>

                        <!-- Hidden inputs for form submission -->
                        <template x-for="user in selectedUsers" :key="user.id">
                            <input type="hidden" name="user_ids[]" :value="user.id" />
                        </template>

                        <!-- Hidden input for project lead ID -->
                        <input type="hidden" name="project_lead_id" x-model="projectLeadId" />

                        <!-- Project Team List -->
                        <div x-show="selectedUsers.length > 0" style="display: none;" 
                            class="p-4 bg-blue-50 dark:bg-gray-800/50 rounded-lg">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Project Team:</p>
                            <div class="space-y-3">
                                <template x-for="user in selectedUsers" :key="user.id">
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-700 p-3 rounded 
                                        border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center flex-1">
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-gray-100" 
                                                    x-text="user.first_name + ' ' + user.last_name"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                            </div>
                                        </div>

                                        <!-- Radio Button for Project Lead -->
                                        <div class="flex items-center mx-4">
                                            <input
                                                type="radio"
                                                :id="'lead_' + user.id"
                                                :value="user.id"
                                                x-model.number="projectLeadId"
                                                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-indigo-600 
                                                    shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                            />
                                            <label :for="'lead_' + user.id" 
                                                class="ms-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                                Lead
                                            </label>
                                        </div>

                                        <!-- Remove Button -->
                                        <button
                                            type="button"
                                            @click="removeUser(user.id)"
                                            class="px-3 py-1 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-800 
                                                dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/50 rounded transition"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <x-primary-button type="submit">
                        Save Project
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
