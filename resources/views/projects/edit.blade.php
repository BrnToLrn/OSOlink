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
                            <input type="date" name="start_date" value="{{ old('start_date', $project->start_date) }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required />
                            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                        </div>
                        <div>
                            <x-input-label for="end_date" :value="__('End Date')" />
                            <input type="date" name="end_date" value="{{ old('end_date', $project->end_date) }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                required />
                            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
                        </div>
                    </div>

                    <!-- User Assignment -->
                    <div>
                        <x-input-label for="user_ids" :value="__('Assign Users (hold Ctrl/Cmd to multi-select)')" />
                        <select name="user_ids[]" id="user_ids" multiple
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            @forelse($users as $user)
                                <option value="{{ $user->id }}" 
                                    {{ (collect(old('user_ids', $project->users->pluck('id')))->contains($user->id)) ? 'selected' : '' }}>
                                    {{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }} — {{ $user->email }} — {{ $user->job_type }}
                                </option>
                            @empty
                                <option disabled>No users available</option>
                            @endforelse
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('user_ids')" />
                    </div>

                    <x-primary-button type="submit">
                        Update Project
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
