<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Edit Dependent
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Update the dependent's information for your profile.
                        </p>
                    </div>
                </header>

                <form method="POST" action="{{ route('dependents.update', $dependent) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" value="{{ old('name', $dependent->name) }}" class="mt-1 block w-full" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Relationship -->
                    <div>
                        <x-input-label for="relationship" :value="__('Relationship')" />
                        <x-text-input id="relationship" name="relationship" type="text" value="{{ old('relationship', $dependent->relationship) }}" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('relationship')" />
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $dependent->date_of_birth)" />
                        <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
                    </div>

                    <x-primary-button type="submit">
                        Save Dependent
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>