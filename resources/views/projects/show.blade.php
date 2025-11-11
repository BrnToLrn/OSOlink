<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">

                <!-- Project Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="space-y-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ $project->name }}
                        </h2>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status: {{ $project->status }}
                        </label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Start Date: {{ \Carbon\Carbon::parse($project->start_date)->format('F j, Y') }}
                        </label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            End Date: {{ \Carbon\Carbon::parse($project->end_date)->format('F j, Y') }}
                        </label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description: {{ $project->description }}
                        </label>
                    </div>

                    @if(auth()->user()->is_admin)
                        <div class="flex space-x-3 ml-6">
                            <x-secondary-button>
                                <a href="{{ route('projects.edit', $project->id) }}">Edit Project</a>
                            </x-secondary-button>
                        </div>
                    @endif
                </div>

                <!-- Assigned Users -->
                <div 
                    x-data="{
                        showModal: false,
                        searchTerm: '',
                        showDropdown: false,
                        selectedUsers: {{ json_encode($selectedUsers, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) }},
                        projectLeadId: {{ $projectLeadId ?? 'null' }},
                        allUsers: {{ json_encode($allUsers, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) }},
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
                        }
                    }" 
                    class="space-y-6"
                >

                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Assigned Users</h2>

                        @if(auth()->user()->is_admin)
                            <x-primary-button @click="showModal = true">Manage Team</x-primary-button>
                        @endif
                    </div>

                    <ul class="space-y-2 mb-4">
                        @foreach($project->users as $user)
                            <li class="border-gray-300 dark:border-gray-700 border rounded-md p-3 dark:bg-gray-900 dark:text-gray-300">
                                <div class="font-medium">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }} | {{ $user->pivot->project_role ?? 'Member' }}</div>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Manage Team Modal -->
                    <div
                        x-show="showModal"
                        x-transition
                        @keydown.escape.window="showModal = false"
                        style="display: none;"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    >
                        <!-- Overlay -->
                        <div 
                            x-show="showModal"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 bg-black bg-opacity-50"
                            @click="showModal = false"
                        ></div>

                        <!-- Modal content -->
                        <div
                            x-show="showModal"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden z-50"
                            @click.outside="showModal = false"
                        >
                            <form method="POST" action="{{ route('projects.update', $project->id) }}">
                                @csrf
                                @method('PUT')

                                <!-- Modal Header -->
                                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Manage Project Team
                                    </h3>
                                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Modal Body -->
                                <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                                    <!-- Search and Add -->
                                    <div>
                                        <x-input-label :value="__('Add Team Member')" />
                                        <div class="relative">
                                            <input
                                                type="text"
                                                x-model="searchTerm"
                                                @focus="showDropdown = true"
                                                @blur="setTimeout(() => showDropdown = false, 200)"
                                                placeholder="Search users by name or email..."
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            />
                                            <div
                                                x-show="showDropdown && filteredUsers.length > 0"
                                                x-cloak
                                                class="absolute top-full left-0 right-0 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg z-10 max-h-64 overflow-y-auto"
                                            >
                                                <template x-for="user in filteredUsers" :key="user.id">
                                                    <button 
                                                        type="button" 
                                                        @mousedown.prevent="addUser(user)" 
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition"
                                                    >
                                                        <div class="font-medium text-gray-900 dark:text-gray-100" x-text="user.first_name + ' ' + (user.middle_name || '') + ' ' + user.last_name"></div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="user.email"></div>
                                                    </button>
                                                </template>
                                                <div x-show="filteredUsers.length === 0" class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">
                                                    No users found
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Project Team List -->
                                    <div x-show="selectedUsers.length > 0" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Project Team:</p>
                                        <div class="space-y-3 max-h-60 overflow-y-auto">
                                            <template x-for="user in selectedUsers" :key="user.id">
                                                <div class="flex items-center justify-between bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                                    <!-- User Info -->
                                                    <div class="flex-1">
                                                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="user.first_name + ' ' + user.last_name"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                                    </div>
                                                    <!-- Radio Button for Project Lead -->
                                                    <div class="flex items-center mx-4">
                                                        <input type="radio" :id="'lead_' + user.id" :value="user.id" x-model.number="projectLeadId" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                                        <label :for="'lead_' + user.id" class="ms-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                                            Lead
                                                        </label>
                                                    </div>
                                                    <!-- Remove Button -->
                                                    <button type="button" @click="removeUser(user.id)" class="px-3 py-1 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded transition">
                                                        Remove
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden inputs for form submission -->
                                <template x-for="user in selectedUsers" :key="user.id">
                                    <input type="hidden" name="user_ids[]" :value="user.id" />
                                </template>
                                <input type="hidden" name="project_lead_id" x-model="projectLeadId" />

                                <!-- Modal Footer -->
                                <div class="flex items-center justify-end p-4 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700">
                                    <x-secondary-button type="button" @click="showModal = false">
                                        Cancel
                                    </x-secondary-button>
                                    <x-primary-button type="submit" class="ms-3">
                                        Save Changes
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


                <!-- Comments & Time Logs -->
                <div x-data="{ section: '{{ request('section', request('edit_timelog') ? 'timelogs' : 'comments') }}' }">
                    <!-- Toggle Buttons -->
                    <div class="flex gap-4 mb-6">
                        <button x-on:click="section = 'comments'" class="px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none" :class="section==='comments'?'bg-indigo-600 text-white hover:bg-indigo-700':'bg-gray-600 text-white hover:bg-gray-700'">Comments</button>
                        <button x-on:click="section = 'timelogs'" class="px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none" :class="section==='timelogs'?'bg-indigo-600 text-white hover:bg-indigo-700':'bg-gray-600 text-white hover:bg-gray-700'">Time Logs</button>
                    </div>

                    <!-- Comments Section -->
                    <div x-show="section === 'comments'">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Comments</h2>
                        <div class="space-y-3">
                            @forelse($project->comments->whereNull('parent_id')->sortByDesc('created_at') as $comment)
                                <div class="border-gray-300 dark:border-gray-700 border rounded-md p-3 dark:bg-gray-900 dark:text-gray-300">
                                    <div class="text-sm text-gray-400">
                                        {{ $comment->user->first_name }} {{ $comment->user->middle_name }} {{ $comment->user->last_name }} · {{ $comment->user->job_type }} · 
                                        <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="mt-2">{{ $comment->content }}</div>
                                    <div class="ml-6 mt-3 space-y-2">
                                        @foreach($comment->replies()->orderBy('created_at')->get() as $reply)
                                            <div class="border-l-2 pl-3">
                                                <div class="text-sm text-gray-400">
                                                    {{ $reply->user->first_name }} {{ $reply->user->middle_name }} {{ $reply->user->last_name }} · {{ $reply->user->job_type }} · 
                                                    <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <div class="mt-1">{{ $reply->content }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <form method="POST" action="{{ route('projects.comments.store', $project->id) }}" class="mt-2 ml-6">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <textarea name="content" rows="2" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required></textarea>
                                        <div class="mt-1">
                                            <x-primary-button>Reply</x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            @empty
                                <p class="text-gray-500">No comments yet.</p>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('projects.comments.store', $project->id) }}" class="mt-4">
                            @csrf
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add a comment</h2>
                            <textarea name="content" rows="3" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>{{ old('content') }}</textarea>
                            <div class="mt-2 flex items-center gap-3">
                                <x-primary-button>Post Comment</x-primary-button>
                                <a href="{{ route('projects.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 dark:bg-gray-700 rounded-md text-white text-xs uppercase tracking-widest hover:bg-gray-600">Back</a>
                            </div>
                        </form>
                    </div>

                    <!-- Time Logs Section -->
                    <div x-show="section === 'timelogs'">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Time Logs</h2>
                        <div class="space-y-4">
                            @foreach($project->timeLogs->sortByDesc('date') as $timeLog)
                                <div class="border-gray-300 dark:border-gray-700 border rounded-md p-4 dark:bg-gray-900 dark:text-gray-300">
                                    @if(request('edit_timelog') == $timeLog->id)
                                        <form method="POST" action="{{ route('projects.updateTimeLog', [$project->id, $timeLog->id]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                <input type="number" name="hours" value="{{ old('hours', $timeLog->hours) }}" step="0.1" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                                <input type="date" name="date" value="{{ old('date', $timeLog->date) }}" min="{{ $project->start_date }}" max="{{ $project->end_date }}" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                                <textarea name="work_output" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm sm:col-span-3">{{ old('work_output', $timeLog->work_output) }}</textarea>
                                            </div>
                                            <div class="mt-4 flex gap-3">
                                                <x-primary-button>Update</x-primary-button>
                                                <a href="{{ route('projects.show', [$project->id, 'section' => 'timelogs']) }}" class="text-gray-500">Cancel</a>
                                            </div>
                                        </form>
                                    @else
                                        <div class="flex justify-between items-center">
                                            <div class="text-sm text-gray-400">
                                                {{ $timeLog->user->first_name }} {{ $timeLog->user->middle_name }} {{ $timeLog->user->last_name }} · {{ $timeLog->user->job_type }} ·
                                                <span class="text-xs text-gray-400">@if($timeLog->date){{ \Carbon\Carbon::parse($timeLog->date)->format('F j, Y') }}@else <em>No date</em> @endif</span>
                                            </div>
                                            <div class="text-sm font-medium">{{ $timeLog->hours }} hours</div>
                                        </div>
                                        <div class="mt-2 text-sm">{{ $timeLog->work_output }}</div>
                                        @if(auth()->user()->is_admin || auth()->id() === $timeLog->user_id)
                                            <div class="mt-2 flex gap-4">
                                                <a href="{{ route('projects.show', [$project->id, 'edit_timelog' => $timeLog->id]) }}" class="text-blue-500">Edit</a>
                                                <form action="{{ route('projects.deleteTimeLog', [$project->id, $timeLog->id]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500" onclick="return confirm('Delete this time log?')">Delete</button>
                                                </form>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(auth()->user()->is_admin || $project->users->contains(auth()->user()->id))
                            <form method="POST" action="{{ route('projects.addTimeLog', $project->id) }}" class="mt-6">
                                @csrf
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add a Time Log</h2>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <input type="number" name="hours" step="0.1" required placeholder="Hours worked" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    <input type="date" name="date" required min="{{ $project->start_date }}" max="{{ $project->end_date }}" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                    <textarea name="work_output" required placeholder="Work details" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm sm:col-span-3"></textarea>
                                </div>
                                <div class="mt-4">
                                    <x-primary-button>Add Time Log</x-primary-button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
