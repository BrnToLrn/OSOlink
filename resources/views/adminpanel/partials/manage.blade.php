<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Manage Users
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Activate or deactivate user accounts.
        </p>
    </header>

    <div class="mt-6 overflow-x-auto">
        <div class="flex flex-wrap items-center gap-3 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <form method="GET" action="{{ route('adminpanel.admin') }}" class="flex flex-wrap gap-3 w-full">
                <!-- Search -->
                <x-text-input 
                    type="text" 
                    name="search" 
                    placeholder="Search users..." 
                    value="{{ request('search') }}"
                    class="w-full sm:w-64"
                />

                <!-- Status Filter -->
                <select name="status" 
                    class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                        focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                <!-- Sort Field -->
                <select name="sort" 
                    class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                        focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="name" {{ request('sort')=='name' ? 'selected' : '' }}>Name</option>
                    <option value="email" {{ request('sort')=='email' ? 'selected' : '' }}>Email</option>
                    <option value="created_at" {{ request('sort')=='created_at' ? 'selected' : '' }}>Date Created</option>
                </select>

                <!-- Sort Order -->
                <select name="order" 
                    class="w-full sm:w-32 rounded-lg border-gray-300 dark:border-gray-600 
                        bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                        focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="asc" {{ request('order')=='asc' ? 'selected' : '' }}>Ascending</option>
                    <option value="desc" {{ request('order')=='desc' ? 'selected' : '' }}>Descending</option>
                </select>

                <!-- Apply Button -->
                
                <div class="flex items-center gap-4">
                    <x-primary-button>
                        Apply
                    </x-primary-button>
                    @if (session('toggle_success'))
                        <p class="text-sm text-green-600 dark:text-green-400">{{ session('toggle_success') }}</p>
                    @elseif (session('selfdeactivation'))
                        <p class="text-sm text-green-600 dark:text-red-400">{{ session('selfdeactivation') }}</p>
                    @endif
                </div>
            </form>
        </div>
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Name</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Email</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Job Type</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Hourly Rate</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Action</th>
                </tr>
            </thead>
            <tbody class="divide-gray-200 dark:bg-gray-900">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</td>
                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</td>

                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $user->job_type ?? '—' }}
                        </td>

                        <td class="px-4 py-2 text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ isset($user->hourly_rate) ? ('CA$' . number_format($user->hourly_rate, 2)) : '—' }}
                        </td>

                        <td class="px-4 py-2 text-center text-sm">
                            <span class="{{ $user->is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                <form id="toggle-form-{{ $user->id }}" action="{{ route('admin.users.toggle', $user) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <a href="#" onclick="event.preventDefault(); document.getElementById('toggle-form-{{ $user->id }}').submit();" class="text-indigo-600 dark:text-indigo-500 hover:underline">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </a>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
