<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- ================================================= -->
            <!--   1. YOUR EXISTING PROJECTS LIST (UNTOUCHED)    -->
            <!-- ================================================= -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-full">
                    
                    <header class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Projects
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                A list of all projects you are assigned to.
                            </p>
                        </div>
                        @if(auth()->user()->is_admin)
                            <x-primary-button :href="route('projects.create')" as="a">
                                New Project
                            </x-primary-button>
                        @endif
                    </header>

                    <!-- Search/filter for the *list* -->
                    <form method="GET" action="{{ route('projects.index') }}" class="mb-4 flex gap-2">
                        <x-text-input name="search" :value="request('search')" placeholder="Search projects by name..." class="w-full"/>
                        <select name="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="Not Started" {{ request('status') == 'Not Started' ? 'selected' : '' }}>Not Started</option>
                            <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="On Hold" {{ request('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        <x-secondary-button type="submit">Filter</x-secondary-button>
                    </form>

                    <!-- Projects Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">End</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Team</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($projects as $project)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('projects.show', $project->id) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $project->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $project->status }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $project->start_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $project->end_date->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $project->users_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No projects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <!-- ================================================= -->
            <!--   2. NEW GLOBAL CALENDAR (ALPINE.JS VERSION)    -->
            <!-- ================================================= -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                
                {{-- This script block defines the Alpine component --}}
                <script>
                    // Pass the data from the controller into a global window variable
                    window.globalCalendarData = {
                        timeLogs: {!! json_encode($calendarLogs) !!},
                        currentUserId: {{ auth()->id() }},
                        allProjects: {!! json_encode($allProjectsForFilter) !!}
                    };

                    function globalTimeLogCalendar(data) {
                        return {
                            weekdays: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                            timeLogs: data.timeLogs || {},
                            currentUserId: data.currentUserId,
                            allProjects: data.allProjects || [],
                            
                            // NEW: This holds the value of the filter dropdown
                            filterProjectId: '{{ request('project_id', 'all') }}', 

                            showModal: false,
                            selectedDate: null,      // 'YYYY-MM-DD'
                            selectedDateFormatted: '', // 'Month D, YYYY'
                            selectedLogs: [],      // Array of logs for the selected date

                            month: new Date().getMonth(),
                            year: new Date().getFullYear(),

                            get monthName() { return new Date(this.year, this.month).toLocaleString('default', { month: 'long' }); },
                            get daysInMonth() { return Array.from({ length: new Date(this.year, this.month + 1, 0).getDate() }, (_, i) => i + 1); },
                            get blanks() { return Array.from({ length: new Date(this.year, this.month, 1).getDay() }, (_, i) => i); },

                            // Helper to get the full YYYY-MM-DD date string
                            getFullDateStr(date) {
                                return `${this.year}-${String(this.month+1).padStart(2,'0')}-${String(date).padStart(2,'0')}`;
                            },
                            
                            // **UPDATED**: Get all logs, but respect the project filter
                            getLogs(date) {
                                const dateStr = this.getFullDateStr(date);
                                let logs = this.timeLogs[dateStr] || [];

                                // If a project is selected, filter the logs
                                if (this.filterProjectId && this.filterProjectId !== 'all') {
                                    return logs.filter(log => log.project_id == this.filterProjectId);
                                }
                                
                                return logs; // Otherwise, return all logs for that day
                            },

                            // Get total hours for a specific day (respects filter)
                            getTotalHours(date) {
                                const logs = this.getLogs(date); // This is now pre-filtered
                                if (!logs.length) return 0;
                                
                                return logs.reduce((total, log) => {
                                    return (log.status !== 'Declined') ? total + parseFloat(log.hours) : total;
                                }, 0).toFixed(2);
                            },
                            
                            // Get total hours for the *entire month* (respects filter)
                            get totalHoursThisMonth() {
                                let total = 0;
                                for (const [dateStr, logs] of Object.entries(this.timeLogs)) {
                                    const dateObj = new Date(dateStr);
                                    if (dateObj.getFullYear() === this.year && dateObj.getMonth() === this.month) {
                                        
                                        // Apply the same filter logic
                                        const filteredLogs = (this.filterProjectId && this.filterProjectId !== 'all')
                                            ? logs.filter(log => log.project_id == this.filterProjectId)
                                            : logs;

                                        filteredLogs.forEach(log => {
                                            if (log.status !== 'Declined') {
                                                total += parseFloat(log.hours);
                                            }
                                        });
                                    }
                                }
                                return total.toFixed(2);
                            },

                            // Get class for the calendar day (respects filter)
                            getDayClass(date) {
                                const logs = this.getLogs(date); // This is now pre-filtered
                                if (logs.length === 0) return 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600';
                                if (logs.some(l => l.status === 'Pending')) return 'bg-yellow-200 dark:bg-yellow-600 hover:bg-yellow-300';
                                if (logs.every(l => l.status === 'Approved')) return 'bg-green-200 dark:bg-green-700 hover:bg-green-300';
                                if (logs.every(l => l.status === 'Declined')) return 'bg-red-200 dark:bg-red-700 hover:bg-red-300';
                                return 'bg-blue-100 dark:bg-blue-800 hover:bg-blue-200'; // Mixed statuses
                            },
                            
                            // Open the modal and set its state
                            openModal(date) {
                                const dateStr = this.getFullDateStr(date);
                                this.selectedDate = dateStr;
                                this.selectedDateFormatted = new Date(this.year, this.month, date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                                this.selectedLogs = this.getLogs(date); // Use the filtered logs
                                this.showModal = true;
                            },
                            
                            closeModal() {
                                this.showModal = false;
                                this.selectedDate = null;
                                this.selectedLogs = [];
                            },
                            
                            prevMonth() {
                                if (this.month === 0) { this.month = 11; this.year--; } else this.month--;
                            },
                            nextMonth() {
                                if (this.month === 11) { this.month = 0; this.year++; } else this.month++;
                            },

                            // Helper for formatting time
                            formatTime(timeStr) {
                                if (!timeStr) return '';
                                let [h, m, s] = timeStr.split(':'); // handle 'HH:MM:SS'
                                let ampm = h >= 12 ? 'PM' : 'AM';
                                h = h % 12 || 12; // 0 or 12 -> 12
                                return `${h}:${m} ${ampm}`;
                            }
                        }
                    }
                </script>

                <!-- initialize Alpine using the global variable -->
                <div x-data="globalTimeLogCalendar(window.globalCalendarData)" class="space-y-4">
                    
                    <!-- Header + Project Filter -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Global Time Log Calendar
                        </h2>

                        <!-- Project Filter Dropdown (NOW uses x-model) -->
                        <div class="flex items-center gap-2">
                            <label for="project_filter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</label>
                            <select 
                                name="project_id" id="project_filter"
                                x-model="filterProjectId"
                                class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">📅 All Your Projects</option>
                                @foreach($allProjectsForFilter as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Month navigation -->
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" class="px-3 py-1 bg-gray-300 dark:bg-gray-700 dark:text-gray-100 rounded">Prev</button>
                        <h2 class="text-lg font-semibold dark:text-gray-100">
                            <span x-text="monthName + ' ' + year"></span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                Total Hours: <span x-text="totalHoursThisMonth"></span>h
                            </span>
                        </h2>
                        <button @click="nextMonth()" class="px-3 py-1 bg-gray-300 dark:bg-gray-700 dark:text-gray-100 rounded">Next</button>
                    </div>

                    <!-- Weekday headers -->
                    <div class="grid grid-cols-7 text-center font-medium text-gray-700 dark:text-gray-300">
                        <template x-for="day in weekdays" :key="day">
                            <div x-text="day" class="py-2"></div>
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
                                    <div x-show="getTotalHours(date) > 0" class="font-semibold dark:text-gray-100">
                                        <span x-text="getTotalHours(date)"></span>h
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- 
                      NEW MODAL
                      This modal now shows a LIST of logs grouped by project.
                    -->
                    <div x-show="showModal" x-cloak class="fixed inset-0 flex items-center justify-center z-50 p-4">
                        <!-- overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-50" @click="closeModal()"></div>
                        
                        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-2xl z-50">
                            
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                                <h3 class="text-lg font-medium dark:text-gray-100" x-text="'Logs for ' + selectedDateFormatted"></h3>
                                <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                
                                <!-- List of existing logs -->
                                <div x-show="selectedLogs.length === 0" class="text-gray-500 dark:text-gray-400 text-sm">
                                    No time logs for this day.
                                </div>

                                <div class="space-y-3">
                                    <template x-for="log in selectedLogs" :key="log.project_id + '-' + log.id">
                                        <div class="p-3 border rounded-md" :class="{
                                            'bg-green-50 dark:bg-green-800/50 border-green-300 dark:border-green-700': log.status == 'Approved',
                                            'bg-yellow-50 dark:bg-yellow-800/50 border-yellow-300 dark:border-yellow-700': log.status == 'Pending',
                                            'bg-red-50 dark:bg-red-800/50 border-red-300 dark:border-red-700': log.status == 'Declined'
                                        }">
                                            
                                            <!-- Top row: Project Name -->
                                            <div class="flex justify-between items-center">
                                                <a :href="`/projects/${log.project_id}`" class="font-semibold dark:text-gray-100 hover:underline" x-text="log.project_name"></a>
                                            </div>

                                            <!-- Log Details (Now underneath) -->
                                            <div class="mt-2">
                                                <!-- Show user name if admin, since this is a global view -->
                                                @if(auth()->user()->is_admin)
                                                <p class="text-sm font-medium dark:text-gray-200" x-text="log.user_name"></p>
                                                @endif
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
                                            
                                            <!-- Show decline reason -->
                                            <div x-show="log.status == 'Declined' && log.decline_reason" class="mt-2 p-2 bg-red-100 dark:bg-red-800/50 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm">
                                                <strong>Reason:</strong> <span x-text="log.decline_reason"></span>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end p-4 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700">
                                <x-secondary-button type="button" @click="closeModal()">
                                    Close
                                </x-secondary-button>
                            </div>
                        </div>
                    </div>

                </div> <!-- End of timeLogCalendar x-data -->
            </div> <!-- End of new calendar div -->
            
        </div>
    </div>
</x-app-layout>