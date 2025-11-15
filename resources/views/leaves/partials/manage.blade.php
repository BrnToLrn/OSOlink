<section>
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Personal Leaves
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Make, view, and search your own leaves.
            </p>
        </div>
        <div class="flex items-center gap-4">
            @if (session('update_success'))
               <p class="text-sm text-green-600 dark:text-green-400">{{ session('update_success') }}</p>
            @elseif (session('create_success'))
               <p class="text-sm text-green-600 dark:text-green-400">{{ session('create_success') }}</p>
            @elseif (session('remove_success'))
               <p class="text-sm text-red-600 dark:text-red-400">{{ session('remove_success') }}</p>
            @endif
            <a href="{{ route('leaves.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent 
                rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 
                dark:hover:bg-indigo-800 focus:bg-indigo-700 dark:focus:bg-indigo-800 focus:outline-none 
                focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Request Leave
            </a>
        </div>
    </header>

    <div class="mt-6 overflow-x-auto">
        <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Type</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">End Date</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Status</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-gray-2 00 dark:bg-gray-900 text-sm">
                @forelse ($leaves as $leave)
                    @php
                        $status = $leave->status ?? 'Pending';
                        $s = strtolower($status);
                        $isPending = $s === 'pending';
                        $statusClass = $s === 'approved' ? 'text-green-600 dark:text-green-400'
                                     : ($s === 'rejected' ? 'text-red-600 dark:text-red-400'
                                     : 'text-yellow-600 dark:text-yellow-400');
                    @endphp
                    <tr>
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">{{ $leave->type }}</td>

                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('F j, Y') : 'N/A' }}
                        </td>

                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            {{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('F j, Y') : 'N/A' }}
                        </td>

                        <td class="px-4 py-2 text-center font-medium {{ $statusClass }}">
                            {{ ucfirst($status) }}
                        </td>
                        
                        <td class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">
                            <div class="inline-flex items-center gap-3">
                                @if($isPending)
                                    <a href="{{ route('leaves.edit', $leave) }}" class="text-green-600 dark:text-green-400 hover:underline">Edit</a>
                                    <form action="{{ route('leaves.destroy', $leave) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                    </form>
                                @else
                                    <span class="text-green-600 dark:text-green-400 opacity-40 cursor-not-allowed select-none">Edit</span>
                                    <span class="text-red-600 dark:text-red-400 opacity-40 cursor-not-allowed select-none">Delete</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">No leaves found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>