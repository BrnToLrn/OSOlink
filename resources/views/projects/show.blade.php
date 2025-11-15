<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">

                <!-- Project Header -->
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $project->name }}
                    </h2>

                    @if(auth()->user()->is_admin)
                        <a href="{{ route('projects.edit', $project->id) }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md 
                                font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm 
                                hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 
                                focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            Edit Project
                        </a>
                    @endif
 
                </div>
                
                <div class="flex items-start justify-between mb-4">
                    <div class="space-y-4">
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
                </div>

                <!-- Assigned Users & Modal -->
                <div 
                    x-data="{
                        showModal: false,
                        searchTerm: '',
                        showDropdown: false,
                        selectedUsers: {{ json_encode($project->users, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) }},
                        projectLeadId: {{ $project->users->firstWhere('pivot.project_role', 'Project Lead')?->id ?? 'null' }},
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
                        @forelse($project->users as $user)
                            <li class="border-gray-300 dark:border-gray-700 border rounded-md p-3 dark:bg-gray-900 dark:text-gray-300">
                                <div class="font-medium">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }} | <span class="font-semibold">{{ $user->pivot->project_role ?? 'Member' }}</span></div>
                            </li>
                        @empty
                             <li class="text-gray-500 dark:text-gray-400">No users are assigned to this project yet.</li>
                        @endforelse
                    </ul>

                    <!-- Manage Team Modal -->
                    <div 
                        x-show="showModal"
                        x-transition
                        @keydown.escape.window="showModal = false"
                        x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    >
                        <!-- Overlay -->
                        <div 
                            class="fixed inset-0 bg-black bg-opacity-50"
                            @click="showModal = false"
                        ></div>

                        <!-- Modal content -->
                        <div
                            class="relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden z-50"
                            @click.outside="showModal = false"
                        >
                            <form method="POST" action="{{ route('projects.update', $project->id) }}">
                                @csrf
                                @method('PUT')

                                <!-- Hidden inputs (so they always submit) -->
                                <template x-for="user in selectedUsers" :key="user.id">
                                    <input type="hidden" name="user_ids[]" x-bind:value="user.id">
                                </template>
                                <input type="hidden" name="project_lead_id" x-bind:value="projectLeadId">

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
                                    <!-- Search box -->
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
                                                class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg z-10 max-h-64 overflow-y-auto"
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
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Team list -->
                                    <div x-show="selectedUsers.length > 0" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Project Team:</p>
                                        <div class="space-y-3 max-h-60 overflow-y-auto">
                                            <template x-for="user in selectedUsers" :key="user.id">
                                                <div class="flex items-center justify-between bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                                    <div class="flex-1">
                                                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="user.first_name + ' ' + user.last_name"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                                    </div>
                                                    <div class="flex items-center mx-4">
                                                        <input type="radio" :id="'lead_' + user.id" :value="user.id" x-model.number="projectLeadId" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                                        <label :for="'lead_' + user.id" class="ms-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                                            Lead
                                                        </label>
                                                    </div>
                                                    <button type="button" @click="removeUser(user.id)" class="px-3 py-1 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded transition">
                                                        Remove
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
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
                </div> <!-- End of x-data div -->

                <!-- Comments & Time Logs -->
                <div x-data="{ section: '{{ request('section', 'comments') }}' }" class="mt-6">
                    <!-- Toggle Buttons -->
                    <div class="flex gap-4 mb-6">
                        <button x-on:click="section = 'comments'" 
                                class="px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none"
                                :class="section==='comments'?'bg-indigo-600 text-white hover:bg-indigo-700':'bg-gray-600 text-white hover:bg-gray-700'">Comments</button>
                        
                        <button x-on:click="section = 'timelogs'" 
                                class="px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none"
                                :class="section==='timelogs'?'bg-indigo-600 text-white hover:bg-indigo-700':'bg-gray-600 text-white hover:bg-gray-700'">Time Logs</button>

                        <!-- The "Approvals" tab is GONE. It's now part of the Time Log modal. -->
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
                                <p class="text-gray-500 dark:text-gray-400">No comments yet.</p>
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
                        
                        <div x-data="timeLogCalendar({
                                timeLogs: {{ json_encode($timeLogs) }},
                                currentUserId: {{ auth()->id() }},
                                isProjectLead: {{ auth()->user()->is_admin || auth()->id() === $projectLeadId ? 'true' : 'false' }},
                                projectId: {{ $project->id }},
                                projectStart: '{{ $project->start_date }}',
                                projectEnd: '{{ $project->end_date }}'
                            })" 
                            class="space-y-4"
                        >

                            <!-- Month navigation -->
                            <div class="flex items-center justify-between mb-4">
                                <button @click="prevMonth()" class="px-3 py-1 bg-gray-300 dark:bg-gray-700 dark:text-gray-100 rounded">Prev</button>
                                <h2 class="text-lg font-semibold dark:text-gray-100">
                                    <span x-text="monthName + ' ' + year"></span>
                                    <!-- Always show user's total -->
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                        Your total hours: <span x-text="totalUserHoursThisMonth"></span>h
                                    </span>
                                    <!-- Show team total only if user is a project lead -->
                                    <template x-if="isProjectLead">
                                        <span class="ml-2 text-sm text-blue-600 dark:text-blue-400">
                                            — Team: <span x-text="totalTeamHoursThisMonth"></span>h —
                                        </span>
                                    </template>
                                </h2>
                                <button @click="nextMonth()" class="px-3 py-1 bg-gray-300 dark:bg-gray-700 dark:text-gray-100  rounded">Next</button>
                            </div>

                            <!-- Weekday headers -->
                            <div class="grid grid-cols-7 text-center font-medium text-gray-700 dark:text-gray-300">
                                <template x-for="day in weekdays" :key="day">
                                    <div x-text="day"></div>
                                </template>
                            </div>

                            <!-- Calendar grid -->
                            <div class="grid grid-cols-7 gap-2 text-center">
                                <!-- Blank days -->
                                <template x-for="blank in blanks" :key="'b'+blank">
                                    <div></div>
                                </template>

                                <!-- Calendar days -->
                                <template x-for="date in daysInMonth" :key="date">
                                    <div @click="openModal(date)" 
                                        class="border rounded-md p-2 cursor-pointer h-24 flex flex-col justify-between"
                                        :class="getDayClass(date)">
                                        <div class="font-medium dark:text-gray-100" x-text="date"></div>
                                        
                                        <!-- Summary of logs -->
                                        <div class="text-xs text-left">
                                            <div x-show="getLogs(date).length > 0" class="flex items-center gap-1">
                                                <span class="dark:text-gray-100" x-text="getLogs(date).length"></span>
                                                <span class="dark:text-gray-100">log(s)</span>
                                            </div>
                                            <div class="dark:text-gray-100" x-show="getTotalHours(date) > 0" class="font-semibold">
                                                <span class="dark:text-gray-100" x-text="getTotalHours(date)"></span>h
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- 
                              NEW MODAL
                              This modal now shows a LIST of logs and the form to add one.
                            -->
                            <div x-show="showModal" x-cloak class="fixed inset-0 flex items-center justify-center z-50 p-4">
                                <!-- overlay -->
                                <div class="absolute inset-0 bg-black opacity-50" @click="closeModal()"></div>
                                
                                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-2xl z-50">
                                    
                                    <!-- Modal Header -->
                                    <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                                        <h3 class="text-lg font-medium dark:text-gray-100" x-text="selectedDateFormatted"></h3>
                                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Modal Body -->
                                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                        
                                        <!-- List of existing logs -->
                                        <!-- Header + Filter Controls (on the same line) -->
                                        <div class="flex items-center justify-between mb-3">
                                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Time Logs</h2>

                                            <div class="flex items-center gap-2">
                                                <button 
                                                    @click="logFilter = 'all'"
                                                    :class="logFilter === 'all'
                                                        ? 'bg-indigo-600 text-white px-2 py-1 rounded text-sm'
                                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-sm'">
                                                    All
                                                </button>
                                                <button 
                                                    @click="logFilter = 'mine'"
                                                    :class="logFilter === 'mine'
                                                        ? 'bg-indigo-600 text-white px-2 py-1 rounded text-sm'
                                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-sm'">
                                                    My Logs
                                                </button>
                                            </div>
                                        </div>
                                        <div x-show="selectedLogs.length === 0" class="text-gray-500 dark:text-gray-400 text-sm">
                                            No time logs for this day.
                                        </div>

                                        <div class="space-y-3">
                                            <template x-for="log in selectedLogs" :key="log.id">
                                                <div class="p-3 border rounded-md" :class="{
                                                    'bg-green-50 dark:bg-green-900/50 border-green-300 dark:border-green-700': log.status == 'Approved',
                                                    'bg-yellow-50 dark:bg-yellow-900/50 border-yellow-300 dark:border-yellow-700': log.status == 'Pending',
                                                    'bg-red-50 dark:bg-red-900/50 border-red-300 dark:border-red-700': log.status == 'Declined'
                                                }">
                                                    <div class="flex justify-between items-center">
                                                        <!-- Left Side: Name -->
                                                        <p class="font-semibold dark:text-gray-100" x-text="log.user_name"></p>
                                                        
                                                        <!-- Right Side: Edit/Delete Buttons -->
                                                        <div x-show="log.user_id == currentUserId || isProjectLead" class="flex gap-2 items-center">
                                                            <button 
                                                                x-show="log.user_id === currentUserId" 
                                                                @click="editLog(log)" 
                                                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                                                Edit
                                                            </button>
                                                            <button 
                                                                x-show="log.status === 'Pending' && log.user_id === currentUserId" 
                                                                @click="deleteLog(log.id)" 
                                                                class="text-xs text-red-600 dark:text-red-400 hover:underline">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Log Details -->
                                                    <div class="mt-2">
                                                        <p class="text-sm dark:text-gray-300">
                                                            <span x-text="formatTime(log.time_in)"></span> - 
                                                            <span x-text="formatTime(log.time_out)"></span>
                                                            (<span x-text="log.hours"></span>h)
                                                        </p>
                                                        <p class="text-xs font-semibold" :class="{
                                                            'text-green-600 dark:text-green-400': log.status == 'Approved',
                                                            'text-yellow-600 dark:text-yellow-400': log.status == 'Pending',
                                                            'text-red-600 dark:text-red-400': log.status == 'Declined'
                                                        }" x-text="log.status"></p>
                                                        <p class="mt-2 text-sm dark:text-gray-300" x-text="log.work_output"></p>
                                                    </div>
                                                    
                                                    <!-- Approval/Decline buttons for Lead -->
                                                    <div x-show="isProjectLead && log.status === 'Pending'" class="flex gap-2 mt-3">
                                                        <!-- Approve -->
                                                        <form :action="`/projects/${projectId}/timelogs/${log.id}/approve`" method="POST">
                                                            @csrf
                                                            <x-primary-button class="text-xs !py-1 !px-2 bg-green-600">Approve</x-primary-button>
                                                        </form>

                                                        <!-- Decline -->
                                                        <div x-data="{ showDecline: false }">
                                                            <x-primary-button @click="showDecline = !showDecline" class="!text-xs !py-1 !px-2 !bg-red-600">Decline</x-primary-button>
                                                            <form x-show="showDecline" :action="`/projects/${projectId}/timelogs/${log.id}/decline`" method="POST" class="mt-2 flex gap-1">
                                                                @csrf
                                                                <x-text-input type="text" name="decline_reason" placeholder="Reason..." required class="!text-xs !py-1 w-full"/>
                                                                <x-secondary-button type="submit" class="!text-xs !py-1 !px-2">Go</x-secondary-button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Show decline reason -->
                                                    <div x-show="log.status == 'Declined' && log.decline_reason" class="mt-2 p-2 bg-red-100 dark:bg-red-800/50 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm">
                                                        <strong>Reason:</strong> <span x-text="log.decline_reason"></span>
                                                    </div>

                                                </div>
                                            </template>
                                        </div>

                                        <hr class="dark:border-gray-700">

                                        <!-- Add/Edit Time Log Form -->
                                        <h4 class="text-md font-semibold dark:text-gray-100" x-text="formTitle"></h4>
                                        <form @submit.prevent="submitLogForm($event)">
                                            @csrf
                                            <input type="hidden" name="_method" x-bind:value="formMethod">
                                            <input type="hidden" name="date" x-bind:value="selectedDate">
                                            <input type="hidden" name="project_id" :value="projectId">
                                            
                                            <div class="space-y-3">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time In</label>
                                                        <input type="time" name="time_in" x-model="formTimeIn" required class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time Out</label>
                                                        <input type="time" name="time_out" x-model="formTimeOut" required class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Output</label>
                                                    <textarea name="work_output" x-model="formWorkOutput" rows="3" required class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                                                </div>
                                                <!-- Show validation errors -->
                                                @if ($errors->has('time_in'))
                                                    <div class="text-sm text-red-600 dark:text-red-400">
                                                        {{ $errors->first('time_in') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" @click="resetForm()" class="px-3 py-1 rounded bg-gray-300 dark:bg-gray-700 text-sm">Cancel</button>
                                                <button type="submit" class="px-3 py-1 rounded bg-indigo-600 text-white text-sm" x-text="formButtonText"></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- End of timeLogCalendar x-data -->

                        <script>
                        function timeLogCalendar(data) {
                            return {
                                weekdays: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                                timeLogs: data.timeLogs || {},
                                currentUserId: data.currentUserId,
                                isProjectLead: data.isProjectLead,
                                projectId: data.projectId,
                                selectedDateFormatted: '',
                                logFilter: 'all',
                                projectStart: data.projectStart,
                                projectEnd: data.projectEnd,
                                
                                isDateAllowed(dateStr) {
                                    const today = new Date();
                                    today.setHours(0, 0, 0, 0); // normalize to start of today

                                    const date = new Date(dateStr);
                                    date.setHours(0, 0, 0, 0); // normalize to start of that day

                                    const start = new Date(this.projectStart);
                                    start.setHours(0, 0, 0, 0);
                                    const end = new Date(this.projectEnd);
                                    end.setHours(0, 0, 0, 0);

                                    return date >= start && date <= end && date <= today;
                                },

                                showModal: false,
                                selectedDate: null,      // 'YYYY-MM-DD'
                                rawSelectedLogs: [],

                                get selectedLogs() {
                                    if (this.logFilter === 'mine') {
                                        return this.rawSelectedLogs.filter(log => log.user_id === this.currentUserId);
                                    }
                                    return this.rawSelectedLogs;
                                },
                                    // Array of logs for the selected date
                                
                                // Form models
                                formTitle: 'Add Time Log',
                                formButtonText: 'Save',
                                formAction: '',
                                formMethod: 'POST',
                                formTimeIn: '',
                                formTimeOut: '',
                                formWorkOutput: '',
                                editingLogId: null,

                                month: new Date().getMonth(),
                                year: new Date().getFullYear(),

                                get monthName() { return new Date(this.year, this.month).toLocaleString('default', { month: 'long' }); },
                                get daysInMonth() { return Array.from({ length: new Date(this.year, this.month + 1, 0).getDate() }, (_, i) => i + 1); },
                                get blanks() { return Array.from({ length: new Date(this.year, this.month, 1).getDay() }, (_, i) => i); },

                                // Helper to get the full YYYY-MM-DD date string
                                getFullDateStr(date) {
                                    return `${this.year}-${String(this.month+1).padStart(2,'0')}-${String(date).padStart(2,'0')}`;
                                },
                                
                                // Get all logs for a specific day
                                getLogs(date) {
                                    const dateStr = this.getFullDateStr(date);
                                    let logs = this.timeLogs[dateStr] || [];

                                    // If user is not project lead/admin, only show their own logs
                                    if (!this.isProjectLead) {
                                        logs = logs.filter(log => log.user_id === this.currentUserId);
                                    }

                                    return logs;
                                },

                                get totalUserHoursThisMonth() {
                                    let total = 0;

                                    // Loop through all logs
                                    for (const [dateStr, logs] of Object.entries(this.timeLogs)) {
                                        const dateObj = new Date(dateStr);
                                        if (dateObj.getFullYear() === this.year && dateObj.getMonth() === this.month) {
                                            logs.forEach(log => {
                                                // Only count current user's logs, and skip declined ones
                                                if (log.user_id === this.currentUserId && log.status !== 'Declined') {
                                                    total += parseFloat(log.hours);
                                                }
                                            });
                                        }
                                    }
                                    return total.toFixed(2);
                                },

                                // Total hours for *all users* (for leads)
                                get totalTeamHoursThisMonth() {
                                    if (!this.isProjectLead) return null; // skip if not a lead

                                    let total = 0;
                                    for (const [dateStr, logs] of Object.entries(this.timeLogs)) {
                                        const dateObj = new Date(dateStr);
                                        if (dateObj.getFullYear() === this.year && dateObj.getMonth() === this.month) {
                                            logs.forEach(log => {
                                                if (log.status !== 'Declined') {
                                                    total += parseFloat(log.hours);
                                                }
                                            });
                                        }
                                    }
                                    return total.toFixed(2);
                                },

                                // Get total hours for a specific day
                                getTotalHours(date) {
                                    const logs = this.getLogs(date);
                                    if (!logs.length) return 0;

                                    // Sum only *approved* or *pending* hours (skip declined)
                                    return logs.reduce((total, log) => {
                                        return (log.status !== 'Declined') ? total + parseFloat(log.hours) : total;
                                    }, 0).toFixed(2);
                                },

                                // Get class for the calendar day
                                getDayClass(date) {
                                    const dateStr = this.getFullDateStr(date);
                                    // Gray out invalid (non-loggable) dates
                                    if (!this.isDateAllowed(dateStr)) {
                                        return 'bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-60';
                                    }

                                    const logs = this.getLogs(date);
                                    if (logs.length === 0) return 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600';
                                    if (logs.some(l => l.status === 'Pending')) return 'bg-yellow-200 dark:bg-yellow-600 hover:bg-yellow-300';
                                    if (logs.every(l => l.status === 'Approved')) return 'bg-green-200 dark:bg-green-700 hover:bg-green-300';
                                    if (logs.every(l => l.status === 'Declined')) return 'bg-red-200 dark:bg-red-700 hover:bg-red-300';
                                    return 'bg-blue-100 dark:bg-blue-800 hover:bg-blue-200'; // Mixed statuses
                                },
                                
                                getCurrentTime() {
                                    const now = new Date();
                                    const hours = String(now.getHours()).padStart(2, '0');
                                    const minutes = String(now.getMinutes()).padStart(2, '0');
                                    return `${hours}:${minutes}`;
                                },

                                // Open the modal and set its state
                                openModal(date) {
                                    const dateStr = this.getFullDateStr(date);

                                    if (!this.isDateAllowed(dateStr)) {
                                        alert('You cannot log time outside the project duration or beyond today.');
                                        return;
                                    }

                                    this.selectedDate = dateStr;
                                    this.selectedDateFormatted = new Date(this.year, this.month, date)
                                        .toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                                    this.rawSelectedLogs = this.timeLogs[dateStr] || [];
                                    this.resetForm();
                                    this.showModal = true;
                                    this.logFilter = 'all';
                                },
                                                                
                                closeModal() {
                                    this.showModal = false;
                                    this.selectedDate = null;
                                    this.selectedLogs = [];
                                    this.resetForm();
                                },

                                // Set up the form for adding a new log
                                resetForm() {
                                    this.formTitle = 'Add New Time Log';
                                    this.formButtonText = 'Save';
                                    this.formAction = `/projects/${this.projectId}/timelogs`;
                                    this.formMethod = 'POST';
                                    this.formTimeIn = '';
                                    this.formTimeOut = this.getCurrentTime();;
                                    this.formWorkOutput = '';
                                    this.editingLogId = null;
                                },

                                // Set up the form for editing an existing log
                                editLog(log) {
                                    this.formTitle = 'Edit Time Log';
                                    this.formButtonText = 'Update';
                                    this.formAction = `/projects/${this.projectId}/timelogs/${log.id}`;
                                    this.formMethod = 'PUT';
                                    this.formTimeIn = log.time_in.substring(0, 5);
                                    this.formTimeOut = log.time_out.substring(0, 5);
                                    this.formWorkOutput = log.work_output;
                                    this.editingLogId = log.id;
                                },

                                submitLogForm(e) {
                                    const form = e.target;
                                    const formData = new FormData(form);

                                    // Get the ACTUAL values from the model (not the display)
                                    const timeIn = this.formTimeIn;   // e.g., "08:00"
                                    const timeOut = this.formTimeOut; // e.g., "08:45"

                                    if (!timeIn || !timeOut) {
                                        alert('Please enter valid times.');
                                        return;
                                    }
                                    
                                    fetch(this.formAction, {
                                        method: this.formMethod, // PUT
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            date: this.selectedDate,
                                            time_in: this.formTimeIn,
                                            time_out: this.formTimeOut,
                                            work_output: this.formWorkOutput
                                        })
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            return response.json().then(data => {
                                                if (data.errors) {
                                                    const errorMessages = Object.values(data.errors).flat().join('\n');
                                                    alert(errorMessages);
                                                } else {
                                                    alert('An unknown error occurred.');
                                                }
                                                return Promise.reject(data);
                                            });
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        location.reload();
                                    })
                                    .catch(async errorData => {
                                        console.error("Fetch failed:", errorData);
                                        if (!errorData.errors) {
                                            alert('An unknown error occurred — check console for details.');
                                        }
                                    });
                                },

                                deleteLog(logId) {
                                    if (!confirm('Are you sure you want to delete this time log?')) {
                                        return;
                                    }

                                    fetch(`/projects/${this.projectId}/timelogs/${logId}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                            'Accept': 'application/json',
                                        }
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            return response.json().then(data => Promise.reject(data));
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        // It worked! Reload the page.
                                        location.reload(); 
                                    })
                                    .catch(errorData => {
                                        alert('An error occurred while deleting the log.');
                                        console.error(errorData);
                                    });
                                },
                                
                                prevMonth() {
                                    if (this.month === 0) {
                                        this.month = 11;
                                        this.year -= 1;
                                    } else {
                                        this.month -= 1;    
                                    }
                                },

                                nextMonth() {
                                    if (this.month === 11) {
                                        this.month = 0;
                                        this.year += 1;
                                    } else {
                                        this.month += 1;
                                    }
                                },

                                // Helper for formatting time
                                formatTime(timeStr) {
                                    if (!timeStr) return '';
                                    let [h, m] = timeStr.split(':');
                                    let ampm = h >= 12 ? 'PM' : 'AM';
                                    h = h % 12 || 12; // 0 or 12 -> 12
                                    return `${h}:${m} ${ampm}`;
                                }
                            }
                        }
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>