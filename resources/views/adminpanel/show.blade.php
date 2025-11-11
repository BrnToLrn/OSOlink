<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        User Information
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View an account's information.
                    </p>
                </header>

                <!-- Profile Picture -->
                <x-input-label for="profile_picture" :value="__('Profile Picture')" class="mt-4"/>
                <div class="flex items-center gap-4 mt-1">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile Picture" width="100" class="rounded-full">
                    @else
                        <img src="{{ asset('images/default-avatar.jpg') }}" alt="Default Avatar" width="100" class="rounded-full">
                    @endif
                </div>

                <div class="mt-6 space-y-6">
                    <div class="flex items-center gap-4 mt-4">
                        <!-- First Name -->
                        <div class="flex-1">
                            <x-input-label :value="__('First Name')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->first_name" disabled />
                        </div>
                        <!-- Middle Name -->
                        <div class="flex-1">
                            <x-input-label :value="__('Middle Name')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->middle_name" disabled />
                        </div>
                        <!-- Last Name -->
                        <div class="flex-1">
                            <x-input-label :value="__('Last Name')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->last_name" disabled />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Email -->
                        <div class="flex-1">
                            <x-input-label :value="__('Email')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->email" disabled />
                        </div>
                        <!-- Phone -->
                        <div class="flex-1">
                            <x-input-label :value="__('Phone')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->phone" disabled />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Gender -->
                        <div class="flex-1">
                            <x-input-label :value="__('Gender')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->gender ?? 'N/A'" disabled />
                        </div>
                        <!-- Birthday -->
                        <div class="flex-1">
                            <x-input-label :value="__('Birthday')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->birthday ? \Carbon\Carbon::parse($user->birthday)->format('F j, Y') : 'N/A'" disabled />
                        </div>
                    </div>

                    <div>
                        <x-input-label :value="__('Address')" />
                        <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->address" disabled />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <!-- Country -->
                        <div class="flex-1">
                            <x-input-label :value="__('Country')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->country" disabled />
                        </div>
                        <!-- State -->
                        <div class="flex-1">
                            <x-input-label :value="__('State')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->state" disabled />
                        </div>
                        <!-- ZIP Code -->
                        <div class="flex-1">
                            <x-input-label :value="__('ZIP Code')" />
                            <x-text-input class="mt-1 block w-full cursor-not-allowed opacity-75" type="text" :value="$user->zip" disabled />
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="flex items-center gap-4 mt-4">
                            <!-- Bank Name -->
                            <div class="flex-1">
                                <x-input-label :value="__('Bank Name')" />
                                <x-text-input class="mt-1 block w-full" type="text" :value="$user->bank_name" />
                            </div>
                            <!-- Bank Account Number -->
                            <div class="flex-1">
                                <x-input-label :value="__('Bank Account Number')" />
                                <x-text-input class="mt-1 block w-full" type="text" :value="$user->bank_account_number" />
                            </div>
                            <!-- Job Type -->
                            <div class="flex-1">
                                <x-input-label for="job_type" :value="__('Job Type')" />
                                <x-text-input id="job_type" name="job_type" type="text" class="mt-1 block w-full" :value="$user->job_type" />
                                    <x-input-error class="mt-2" :messages="$errors->get('job_type')" />
                            </div>

                            <!-- Hourly Rate -->
                            <div class="flex-1">
                                <x-input-label for="hourly_rate" :value="__('Hourly Rate')" />
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm text-gray-600 dark:text-gray-300">CA$</span>
                                    <x-text-input id="hourly_rate"
                                                name="hourly_rate"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="mt-0 block w-full pl-14"
                                                :value="$user->hourly_rate"
                                                required />
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('hourly_rate')" />
                            </div>
                        </div>
                        
                        <div class="flex justify-between gap-4 mt-4">
                            <div class="flex items-center gap-4">
                                <x-primary-button>Update User</x-primary-button>
                                @if (session('update_success'))
                                    <p class="text-sm text-green-600 dark:text-green-400">{{ session('update_success') }}</p>
                                @endif
                            </div>

                            <!-- Admin Checkbox -->
                            <div class="flex items-center">
                                <input id="is_admin" name="is_admin" type="checkbox" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 {{ $user->id === Auth::id() ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-70' : '' }}"
                                {{ $user->is_admin ? 'checked' : '' }}
                                {{ $user->id === Auth::id() ? 'disabled' : '' }}>
                                <x-input-label for="is_admin" :value="__('Set user as admin')" class="ml-2" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>