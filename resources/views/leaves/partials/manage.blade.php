<section class="space-y-12 antialiased">

    {{-- Personal Leave Dashboard --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Personal Leave Dashboard</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Remaining leave requests per type this fiscal year.
                </p>
            </div>
        </div>

        @foreach (['create_success','update_success','remove_success'] as $msg)
            @if(session($msg))
                <div class="mt-4 text-sm font-medium text-green-600 dark:text-green-400">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach

        @php
            $colorMap = [
                'Sick Leave' => [
                    'text' => 'text-rose-500 dark:text-rose-300',
                    'bar'  => 'bg-rose-600',
                    'soft' => 'bg-rose-100/60 dark:bg-rose-900/60',
                ],
                'Vacation Leave' => [
                    'text' => 'text-indigo-500 dark:text-indigo-300',
                    'bar'  => 'bg-indigo-600',
                    'soft' => 'bg-indigo-100/60 dark:bg-indigo-900/60',
                ],
                'Bereavement Leave' => [
                    'text' => 'text-slate-500 dark:text-slate-300',
                    'bar'  => 'bg-slate-600',
                    'soft' => 'bg-slate-100/60 dark:bg-slate-900/60',
                ],
                'Emergency/Personal Leave' => [
                    'text' => 'text-amber-500 dark:text-amber-300',
                    'bar'  => 'bg-amber-600',
                    'soft' => 'bg-amber-100/60 dark:bg-amber-900/60',
                ],
                'Mandatory Leave' => [
                    'text' => 'text-emerald-500 dark:text-emerald-300',
                    'bar'  => 'bg-emerald-600',
                    'soft' => 'bg-emerald-100/60 dark:bg-emerald-900/60',
                ],
            ];
        @endphp

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @forelse($counters as $c)
                @php
                    $used      = (int)$c->used;
                    $allowance = (int)$c->allowance;
                    $remain    = max(0, $allowance - $used);
                    $pct       = $allowance > 0 ? round(($remain / $allowance) * 100) : 0;
                    $colors    = $colorMap[$c->leave_type] ?? [
                        'text' => 'text-gray-600 dark:text-gray-300',
                        'bar'  => 'bg-gray-600',
                        'soft' => 'bg-gray-100/60 dark:bg-gray-900/60',
                    ];
                    $exhausted = $remain === 0;
                @endphp

                <div class="relative rounded-lg p-5 shadow {{ $colors['soft'] }} flex flex-col items-center justify-center">
                    <div class="absolute top-3 right-3 text-xs font-semibold px-2 py-1 rounded
                        {{ $exhausted ? 'bg-red-600 text-white' : 'bg-gray-800 text-white dark:bg-gray-700' }}">
                        {{ $pct }}%
                    </div>

                    <div class="text-4xl md:text-5xl font-extrabold leading-none tracking-tight tabular-nums
                        {{ $exhausted ? 'text-red-600 dark:text-red-400' : $colors['text'] }}">
                        {{ $remain }}
                    </div>

                    <div class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-100 text-center">
                        {{ $c->leave_type }}
                    </div>
                    <div class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                        Remaining of {{ $allowance }} requests
                    </div>

                    <div class="mt-3 w-full h-2 rounded bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div class="h-2 {{ $exhausted ? 'bg-red-600' : $colors['bar'] }}" style="width: {{ $pct }}%"></div>
                    </div>

                    @if($exhausted)
                        <div class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">No requests left</div>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-sm text-gray-600 dark:text-gray-400">
                    No counters yet.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Manage Personal Leaves --}}
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
                <tbody class="dark:bg-gray-900 text-sm">
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

    {{-- Status Change History (only for this user's leaves) --}}
    @php
        $history = $statusHistory ?? collect();
    @endphp
    <section>
        <h2 class="mt-10 text-lg font-medium text-gray-900 dark:text-gray-100">Leave Status Change History</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Approvals and rejections tied to your leave requests.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="rounded-lg overflow-hidden w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Leave</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Period</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Action</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">By</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-700 dark:text-gray-200">Date</th>
                    </tr>
                </thead>
                <tbody class="dark:bg-gray-900 text-sm">
                    @forelse ($history as $h)
                        @php
                            $action = ucfirst(strtolower($h->action ?? ''));
                            $actionClass = $action === 'Approved'
                                ? 'text-green-600 dark:text-green-400'
                                : ($action === 'Rejected'
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-gray-700 dark:text-gray-300');

                            $leaveItem = $h->leave ?? null;
                            $by = $h->changedBy ?? null;
                            $byName = $by
                                ? trim(($by->first_name ?? '').' '.($by->middle_name ?? '').' '.($by->last_name ?? '')) ?: ($by->name ?? 'User')
                                : 'System';

                            $startFmt = $leaveItem? \Carbon\Carbon::parse($leaveItem->start_date)->format('M j, Y') : '—';
                            $endFmt   = $leaveItem? \Carbon\Carbon::parse($leaveItem->end_date)->format('M j, Y')   : '—';
                            $whenFmt  = $h->occurred_at
                                ? $h->occurred_at->timezone(config('app.timezone'))->format('M j, Y g:i A')
                                : '—';
                        @endphp
                        <tr>
                            <td class="px-4 py-2 text-center text-gray-800 dark:text-gray-200">
                                {{ $leaveItem?->type ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-center text-gray-800 dark:text-gray-200">
                                {{ $startFmt }} – {{ $endFmt }}
                            </td>
                            <td class="px-4 py-2 text-center font-medium {{ $actionClass }}">
                                {{ $action }}
                            </td>
                            <td class="px-4 py-2 text-center text-gray-800 dark:text-gray-200">
                                {{ $byName }}
                            </td>
                            <td class="px-4 py-2 text-center text-gray-800 dark:text-gray-200">
                                {{ $whenFmt }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                                No status changes recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</section>