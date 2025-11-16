<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Requests-based quota (each Pending or Approved leave = 1)
    public function index(Request $request)
    {
        $user = Auth::user();

        $leaves = Leave::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $personalLeaves = $leaves;

        $globalLeaves = null;
        if ($user->is_admin ?? false) {
            $globalLeaves = Leave::with('user')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        }

        $tz         = $this->resolvedTimezone();
        $today      = Carbon::today($tz);
        $fyStart    = $this->fiscalStartMonth();
        $fiscalYear = $this->fiscalYearOfDate($today, $fyStart);

        $this->ensureAllCounters($user->id, $fiscalYear);

        $counters = LeaveCounter::where('user_id', $user->id)
            ->where('year', $fiscalYear)
            ->orderBy('leave_type')
            ->get();

        return view('leaves.index', compact('leaves','personalLeaves','globalLeaves','counters'));
    }

    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'start_date' => Carbon::today($this->resolvedTimezone())->toDateString(),
            'status'     => 'Pending',
        ]);

        $data = $request->validate([
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'type'       => ['required','string','max:100'],
            'reason'     => ['nullable','string'],
            'status'     => ['required','string','in:Pending,Approved,Rejected'],
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'], $tz);
        $end   = Carbon::parse($data['end_date'],   $tz);

        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start, $fyStart) !== $this->fiscalYearOfDate($end, $fyStart)) {
            return back()->withErrors(['end_date' => 'Start and end must be within same fiscal year.'])->withInput();
        }

        $userId     = Auth::id();
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $type       = $data['type'];

        $this->ensureAllCounters($userId, $fiscalYear);

        $counter   = $this->ensureCounter($userId, $type, $fiscalYear);
        $used      = $this->requestsUsed($userId, $type, $fiscalYear);
        $remaining = max(0, $counter->allowance - $used);

        if ($remaining <= 0) {
            return back()->withErrors([
                'type' => "No remaining {$type} requests. Used: {$used} of {$counter->allowance}."
            ])->withInput();
        }

        $data['user_id'] = $userId;
        Leave::create($data);

        $this->ensureCounter($userId, $type, $fiscalYear);

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

        $request->merge([
            'start_date' => Carbon::today($this->resolvedTimezone())->toDateString(),
        ]);

        $data = $request->validate([
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'type'       => ['required','string','max:100'],
            'reason'     => ['nullable','string'],
            'status'     => ['nullable','string','in:Pending,Approved,Rejected'],
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'], $tz);
        $end   = Carbon::parse($data['end_date'],   $tz);

        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start, $fyStart) !== $this->fiscalYearOfDate($end, $fyStart)) {
            return back()->withErrors(['end_date' => 'Start and end must be within same fiscal year.'])->withInput();
        }

        $userId     = $leave->user_id;
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $oldType    = $leave->type;
        $newType    = $data['type'];

        $this->ensureAllCounters($userId, $fiscalYear);

        if ($oldType !== $newType) {
            $counter = $this->ensureCounter($userId, $newType, $fiscalYear);
            $usedNew = Leave::where('user_id', $userId)
                ->where('type', $newType)
                ->where('status', '!=', 'Rejected')
                ->whereDate('start_date', '>=', Carbon::create($start->year, 1, 1)->toDateString())
                ->whereDate('start_date', '<=', Carbon::create($start->year,12,31)->toDateString())
                ->where('id', '!=', $leave->id)
                ->count();

            if ($usedNew + 1 > $counter->allowance) {
                return back()->withErrors([
                    'type' => "No remaining {$newType} requests. Used: {$usedNew} of {$counter->allowance}."
                ])->withInput();
            }
        }

        if (!$this->isAdmin()) {
            $data['status'] = 'Pending';
        }

        $leave->update($data);

        $this->ensureCounter($userId, $oldType, $fiscalYear);
        $this->ensureCounter($userId, $newType, $fiscalYear);

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

    public function approve(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz    = $this->resolvedTimezone();
        $today = Carbon::today($tz);
        $end   = Carbon::parse($leave->end_date, $tz);

        if ($end->lt($today)) {
            return back()->withErrors(['status' => 'Cannot approve past leave.']);
        }

        $leave->update(['status' => 'Approved']);
        return back()->with('global_update_success', $this->globalStatusMessage('Approved'));
    }

    public function reject(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz    = $this->resolvedTimezone();
        $today = Carbon::today($tz);
        $end   = Carbon::parse($leave->end_date, $tz);

        if ($end->lt($today)) {
            return back()->withErrors(['status' => 'Cannot reject past leave.']);
        }

        $leave->update(['status' => 'Rejected']);

        $start      = Carbon::parse($leave->start_date, $tz);
        $fyStart    = $this->fiscalStartMonth();
        $fiscalYear = $this->fiscalYearOfDate($start, $fyStart);
        $this->ensureCounter($leave->user_id, $leave->type, $fiscalYear);

        return back()->with('global_update_success', $this->globalStatusMessage('Rejected'));
    }

    public function pending(Leave $leave)
    {
        $this->authorizeAdmin();

        $tz  = $this->resolvedTimezone();
        $end = Carbon::parse($leave->end_date, $tz);
        if ($end->lt(Carbon::today($tz))) {
            return back()->withErrors(['status' => 'Cannot change status of a past leave.']);
        }

        $leave->update(['status' => 'Pending']);
        return back()->with('global_update_success', $this->globalStatusMessage('Pending'));
    }

    // ----------------- Helpers -----------------

    private function resolvedTimezone(): string
    {
        return (string)(Auth::user()->timezone ?? config('app.timezone', 'UTC'));
    }

    private function isAdmin(): bool
    {
        return (bool)(Auth::user()->is_admin ?? false);
    }

    private function authorizeAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }

    private function isOwnerOrAdmin(Leave $leave): bool
    {
        $user = Auth::user();
        return $leave->user_id === $user->id || ($user->is_admin ?? false);
    }

    private function isPending(Leave $leave): bool
    {
        return strcasecmp((string)$leave->status, 'Pending') === 0;
    }

    private function authorizeView(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave), 403);
    }

    private function authorizeMutable(Leave $leave): void
    {
        // Only owner/admin AND only when status is Pending
        abort_unless($this->isOwnerOrAdmin($leave) && $this->isPending($leave), 403);
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

    private function requestsUsed(int $userId, string $type, int $fiscalYear): int
    {
        $tz           = $this->resolvedTimezone();
        $fyStartMonth = $this->fiscalStartMonth();
        [$periodStart, $periodEnd] = $this->fiscalPeriodBounds($fiscalYear, $fyStartMonth, $tz);

        return Leave::where('user_id', $userId)
            ->where('type', $type)
            ->whereIn('status', ['Pending','Approved'])
            ->whereDate('start_date', '>=', $periodStart->toDateString())
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->count();
    }

    private function globalStatusMessage(string $status): string
    {
        return 'Leave status is set to ' . ucfirst(strtolower($status));
    }
}