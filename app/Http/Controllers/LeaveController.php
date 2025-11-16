<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveCounter;
use App\Models\LeaveStatusHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Dashboard/index
    public function index(Request $request)
    {
        $user = Auth::user();

        // Personal leaves
        $personalLeaves = Leave::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        // Alias to satisfy partials that expect $leaves
        $leaves = $personalLeaves;

        // Global leaves for admins (if needed by your index view)
        $globalLeaves = null;
        if ($user->is_admin ?? false) {
            $globalLeaves = Leave::with('user')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        }

        // Counters for dashboard cards
        $tz         = $this->resolvedTimezone();
        $today      = Carbon::today($tz);
        $fyStart    = $this->fiscalStartMonth();
        $fiscalYear = $this->fiscalYearOfDate($today, $fyStart);
        $this->ensureAllCounters($user->id, $fiscalYear);

        $counters = LeaveCounter::where('user_id', $user->id)
            ->where('year', $fiscalYear)
            ->orderBy('leave_type')
            ->get();

        // Status-change history for this user's leaves
        $statusHistory = LeaveStatusHistory::with(['leave', 'changedBy'])
            ->whereHas('leave', fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return view('leaves.index', compact(
            'personalLeaves',
            'leaves',
            'globalLeaves',
            'counters',
            'statusHistory'
        ));
    }

    public function create()
    {
        return view('leaves.create');
    }

    // Store new leave
    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            // Strictly after start (not the same date)
            'end_date'   => ['required', 'date', 'after:start_date'],
            'type'       => ['required', 'string', 'max:100'],
            'reason'     => ['nullable', 'string', 'max:5000'],
        ], [
            'end_date.after' => 'End date must be after start date.',
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'], $tz);
        $end   = Carbon::parse($data['end_date'],   $tz);

        // NEW RULE IMPLEMENTATION:
        // Prevent filing of overlapping leave dates if user has Pending or Approved leave.
        // Allowed only if the overlapping leave is Rejected.
        if ($this->hasOverlappingLeave(Auth::id(), $start, $end)) {
            return back()->withErrors([
                'start_date' => 'You already have an existing pending or approved leave that overlaps this date range.',
            ])->withInput();
        }

        // Optional: keep within same fiscal year
        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start, $fyStart) !== $this->fiscalYearOfDate($end, $fyStart)) {
            return back()->withErrors(['end_date' => 'Start and end must be within the same fiscal year.'])->withInput();
        }

        $leave = new Leave($data);
        $leave->user_id = Auth::id();
        $leave->status  = 'Pending';
        $leave->save();

        // Refresh counters
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $this->ensureCounter($leave->user_id, $leave->type, $fiscalYear);

        return redirect()->route('leaves.index')->with('create_success', 'Leave request filed.');
    }

    public function show(Leave $leave)
    {
        $this->authorizeView($leave);
        return view('leaves.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        $this->authorizeMutable($leave);
        return view('leaves.edit', compact('leave'));
    }

    public function update(Request $request, Leave $leave)
    {
        $this->authorizeMutable($leave);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            // Strictly after start (not the same date)
            'end_date'   => ['required', 'date', 'after:start_date'],
            'type'       => ['required', 'string', 'max:100'],
            'reason'     => ['nullable', 'string', 'max:5000'],
        ], [
            'end_date.after' => 'End date must be after start date.',
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'], $tz);
        $end   = Carbon::parse($data['end_date'],   $tz);

        // NEW RULE IMPLEMENTATION:
        // Prevent overlapping date ranges except when the overlapping leave is Rejected.
        // Ignore the current leave while checking (so user can edit without false collision).
        if ($this->hasOverlappingLeave($leave->user_id, $start, $end, $leave->id)) {
            return back()->withErrors([
                'start_date' => 'You already have an existing pending or approved leave that overlaps this date range.',
            ])->withInput();
        }

        // Optional: keep within same fiscal year
        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start, $fyStart) !== $this->fiscalYearOfDate($end, $fyStart)) {
            return back()->withErrors(['end_date' => 'Start and end must be within the same fiscal year.'])->withInput();
        }

        $oldType = $leave->type;
        $leave->update($data);

        // Refresh counters for old/new types
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $this->ensureCounter($leave->user_id, $oldType, $fiscalYear);
        $this->ensureCounter($leave->user_id, $leave->type, $fiscalYear);

        return redirect()->route('leaves.index')->with('update_success', 'Leave updated.');
    }

    public function destroy(Leave $leave)
    {
        $this->authorizeMutable($leave);

        $tz         = $this->resolvedTimezone();
        $start      = Carbon::parse($leave->start_date, $tz);
        $fyStart    = $this->fiscalStartMonth();
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $type       = $leave->type;
        $userId     = $leave->user_id;

        $leave->delete();
        $this->ensureCounter($userId, $type, $fiscalYear);

        return redirect()->route('leaves.index')->with('remove_success', 'Leave deleted.');
    }

    // Admin: Approve
    public function approve(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz    = $this->resolvedTimezone();
        $today = Carbon::today($tz);
        $end   = Carbon::parse($leave->end_date, $tz);

        if ($end->lt($today)) {
            return back()->withErrors(['status' => 'Cannot approve past leave.']);
        }

        if (strcasecmp((string)$leave->status, 'Approved') !== 0) {
            $leave->update(['status' => 'Approved']);

            LeaveStatusHistory::create([
                'leave_id'    => $leave->id,
                'action'      => 'Approved',
                'changed_by'  => Auth::id(),
                'occurred_at' => now($tz),
            ]);
        }

        return back()->with('global_update_success', $this->globalStatusMessage('Approved'));
    }

    // Admin: Reject
    public function reject(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz    = $this->resolvedTimezone();
        $today = Carbon::today($tz);
        $end   = Carbon::parse($leave->end_date, $tz);

        if ($end->lt($today)) {
            return back()->withErrors(['status' => 'Cannot reject past leave.']);
        }

        if (strcasecmp((string)$leave->status, 'Rejected') !== 0) {
            $leave->update(['status' => 'Rejected']);

            LeaveStatusHistory::create([
                'leave_id'    => $leave->id,
                'action'      => 'Rejected',
                'changed_by'  => Auth::id(),
                'occurred_at' => now($tz),
            ]);
        }

        return back()->with('global_update_success', $this->globalStatusMessage('Rejected'));
    }

    // Admin: Set Pending (blocked once approved/rejected)
    public function pending(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz  = $this->resolvedTimezone();
        $end = Carbon::parse($leave->end_date, $tz);

        if ($end->lt(Carbon::today($tz))) {
            return back()->withErrors(['status' => 'Cannot change status of a past leave.']);
        }

        $current = strtolower((string)($leave->status ?? ''));
        if (in_array($current, ['approved', 'rejected'], true)) {
            return back()->withErrors(['status' => 'Cannot revert an Approved or Rejected leave back to Pending.']);
        }

        if ($current !== 'pending') {
            $leave->update(['status' => 'Pending']);
        }

        return back()->with('global_update_success', $this->globalStatusMessage('Pending'));
    }

    // ---------- AuthZ helpers ----------

    private function authorizeView(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave), 403);
    }

    private function authorizeMutable(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave) && $this->isPending($leave), 403);
    }

    private function isOwnerOrAdmin(Leave $leave): bool
    {
        $user = Auth::user();
        return $leave->user_id === $user->id || (bool)($user->is_admin ?? false);
    }

    private function isPending(Leave $leave): bool
    {
        return strcasecmp((string)$leave->status, 'Pending') === 0;
    }

    private function authorizeAdmin(): void
    {
        abort_unless((bool)(Auth::user()->is_admin ?? false), 403);
    }

    // ---------- Time/fiscal helpers ----------

    private function resolvedTimezone(): string
    {
        return (string)(Auth::user()->timezone ?? config('app.timezone', 'UTC'));
    }

    private function fiscalStartMonth(): int
    {
        $m = (int)config('leaves.fiscal_year_start_month', 1);
        return min(max($m, 1), 12);
    }

    private function fiscalYearOfDate(Carbon $date, int $fyStartMonth): int
    {
        return ($date->month >= $fyStartMonth) ? $date->year : ($date->year - 1);
    }

    private function fiscalPeriodBounds(int $fyStartYear, int $fyStartMonth, string $tz): array
    {
        $start = Carbon::create($fyStartYear, $fyStartMonth, 1, 0, 0, 0, $tz)->startOfDay();
        $end   = (clone $start)->addYear()->subDay()->endOfDay();
        return [$start, $end];
    }

    // ---------- Counters ----------

    private function ensureAllCounters(int $userId, int $fiscalYear): void
    {
        foreach ($this->leaveTypes() as $type) {
            $this->ensureCounter($userId, $type, $fiscalYear);
        }
    }

    private function ensureCounter(int $userId, string $type, int $fiscalYear): LeaveCounter
    {
        $counter = LeaveCounter::firstOrCreate(
            ['user_id' => $userId, 'leave_type' => $type, 'year' => $fiscalYear],
            ['allowance' => $this->defaultAllowance($type), 'used' => 0]
        );

        $counter->used = $this->requestsUsed($userId, $type, $fiscalYear);
        $counter->save();

        return $counter;
    }

    private function requestsUsed(int $userId, string $type, int $fiscalYear): int
    {
        $tz           = $this->resolvedTimezone();
        $fyStartMonth = $this->fiscalStartMonth();
        [$periodStart, $periodEnd] = $this->fiscalPeriodBounds($fiscalYear, $fyStartMonth, $tz);

        return Leave::where('user_id', $userId)
            ->where('type', $type)
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereDate('start_date', '>=', $periodStart->toDateString())
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->count();
    }

    private function defaultAllowance(string $type): int
    {
        $map = config('leaves.allowances', []);
        $fallback = (int)config('leaves.default_allowance', 5);
        return (int)($map[$type] ?? $fallback);
    }

    private function leaveTypes(): array
    {
        $types = config('leaves.types', []);
        if (is_array($types) && count($types)) return $types;

        $map = config('leaves.allowances', []);
        if (count($map)) return array_keys($map);

        return [
            'Sick Leave',
            'Vacation Leave',
            'Bereavement Leave',
            'Emergency/Personal Leave',
            'Mandatory Leave',
        ];
    }

    // ---------- Misc ----------

    private function globalStatusMessage(string $status): string
    {
        return 'Leave status is set to ' . ucfirst(strtolower($status));
    }

    // ---------- NEW HELPER FOR DATE OVERLAP RULE ----------

    /**
     * Prevent user from filing overlapping leave requests
     * unless the overlapping request is Rejected.
     */
    private function hasOverlappingLeave($userId, Carbon $start, Carbon $end, $ignoreId = null): bool
    {
        return Leave::where('user_id', $userId)
            ->whereIn('status', ['Pending', 'Approved'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                $q->whereDate('start_date', '<=', $end->toDateString())
                  ->whereDate('end_date', '>=', $start->toDateString());
            })
            ->exists();
    }
}
