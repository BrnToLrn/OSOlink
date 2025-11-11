<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <header class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Manage Projects
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Search, filter, and sort projects.
                        </p>
                    </div>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('projects.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent 
                           rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 
                           dark:hover:bg-indigo-800 focus:bg-indigo-700 dark:focus:bg-indigo-800 focus:outline-none 
                           focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Create Project
                        </a>
                    @endif
                </header>

                <div class="mt-6 overflow-x-auto">
                    <div class="flex flex-wrap items-center gap-3 mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <form method="GET" action="{{ route('projects.index') }}" class="flex flex-wrap gap-3 w-full">
                            <!-- Search -->
                            <x-text-input 
                                type="text" 
                                name="search" 
                                placeholder="Search projects..." 
                                value="{{ request('search') }}"
                                class="w-full sm:w-64"
                            />

                            <!-- Status Filter -->
                            <select name="status" 
                                class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                                    bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">All Status</option>
                                <option value="Not Started" {{ request('status')=='Not Started' ? 'selected' : '' }}>Not Started</option>
                                <option value="In Progress" {{ request('status')=='In Progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="On Hold" {{ request('status')=='On Hold' ? 'selected' : '' }}>On Hold</option>
                                <option value="Completed" {{ request('status')=='Completed' ? 'selected' : '' }}>Completed</option>
                            </select>

                            <!-- Sort Field -->
                            <select name="sort_by" 
                                class="w-full sm:w-40 rounded-lg border-gray-300 dark:border-gray-600 
                                    bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="name" {{ request('sort_by')=='name' ? 'selected' : '' }}>Name</option>
                                <option value="status" {{ request('sort_by')=='status' ? 'selected' : '' }}>Status</option>
                                <option value="start_date" {{ request('sort_by')=='start_date' ? 'selected' : '' }}>Start Date</option>
                                <option value="end_date" {{ request('sort_by')=='end_date' ? 'selected' : '' }}>End Date</option>
                            </select>

                            <!-- Sort Order -->
                            <select name="sort_dir" 
                                class="w-full sm:w-32 rounded-lg border-gray-300 dark:border-gray-600 
                                    bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 
                                    focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="asc" {{ request('sort_dir')=='asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ request('sort_dir')=='desc' ? 'selected' : '' }}>Descending</option>
                            </select>

                            <!-- Apply Button -->
                            <x-primary-button>
                                Apply
                            </x-primary-button>
                        </form>
                    </div>

                    <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">Name</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">Status</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">Start Date</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">End Date</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-700 dark:text-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-gray-200 dark:bg-gray-900 text-sm">
                            @forelse ($projects as $project)
                                <tr>
                                    <td class="px-4 py-2 text-center text-gray-900 dark:text-gray-100 font-semibold">
                                        {{ $project->name }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="
                                            @if($project->status == 'Completed') text-green-600 dark:text-green-400
                                            @elseif($project->status == 'In Progress') text-gray-600 dark:text-gray-400
                                            @else text-gray-600 dark:text-gray-400
                                            @endif
                                        ">
                                            {{ $project->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center text-gray-900 dark:text-gray-100">
                                        {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center text-gray-900 dark:text-gray-100">
                                        {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="{{ route('projects.show', $project->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                            @if(auth()->user()->is_admin)
                                                <a href="{{ route('projects.edit', $project->id) }}" class="text-green-600 dark:text-green-400 hover:underline">Edit</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-center text-gray-600 dark:text-gray-400">No projects found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>