<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveCounter;
use App\Models\LeaveStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ---------------- Dashboard ----------------
    public function index(Request $request)
    {
        $user      = Auth::user();
        $tz        = $this->resolvedTimezone();
        $fyStart   = $this->fiscalStartMonth();
        $currentFY = $this->fiscalYearOfDate(Carbon::today($tz), $fyStart);

        $activeFY = (int) (session('leaves_active_year') ?: $request->query('year') ?: $currentFY);
        session(['leaves_active_year' => $activeFY]);

        // Rebuild / ensure counters (recompute only for new leaves after cutoff)
        $this->ensureAllCounters($user->id, $activeFY);

        $counters = LeaveCounter::where('user_id', $user->id)
            ->where('year', $activeFY)
            ->orderBy('leave_type')
            ->get();

        $personalLeaves = Leave::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $leaves = $personalLeaves;

        $globalLeaves = null;
        if ($user->is_admin ?? false) {
            $globalLeaves = Leave::with('user')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        }

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
        ))->with([
            'activeYear'  => $activeFY,
            'currentYear' => $currentFY,
        ]);
    }

    // ---------------- CRUD (User) ----------------
    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after:start_date'],
            'type'       => ['required','string','max:100'],
            'reason'     => ['nullable','string','max:5000'],
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'], $tz);
        $end   = Carbon::parse($data['end_date'],   $tz);

        if ($this->hasOverlappingLeave(Auth::id(), $start, $end)) {
            return back()->withErrors(['start_date' => 'Overlapping pending/approved leave.'])->withInput();
        }

        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start,$fyStart) !== $this->fiscalYearOfDate($end,$fyStart)) {
            return back()->withErrors(['end_date'=>'Start and end must be in same fiscal year.'])->withInput();
        }

        $leave = new Leave($data);
        $leave->user_id = Auth::id();
        $leave->status  = 'Pending';
        $leave->save();

        $fy = $this->fiscalYearOfDate($start,$fyStart);
        $this->ensureCounter($leave->user_id, $leave->type, $fy);

        return redirect()->route('leaves.index')->with('create_success','Leave request filed.');
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
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after:start_date'],
            'type'       => ['required','string','max:100'],
            'reason'     => ['nullable','string','max:5000'],
        ]);

        $tz    = $this->resolvedTimezone();
        $start = Carbon::parse($data['start_date'],$tz);
        $end   = Carbon::parse($data['end_date'],$tz);

        if ($this->hasOverlappingLeave($leave->user_id,$start,$end,$leave->id)) {
            return back()->withErrors(['start_date'=>'Overlapping pending/approved leave.'])->withInput();
        }

        $fyStart = $this->fiscalStartMonth();
        if ($this->fiscalYearOfDate($start,$fyStart) !== $this->fiscalYearOfDate($end,$fyStart)) {
            return back()->withErrors(['end_date'=>'Start and end must match fiscal year.'])->withInput();
        }

        $oldType = $leave->type;
        $leave->update($data);

        $fy = $this->fiscalYearOfDate($start,$fyStart);
        $this->ensureCounter($leave->user_id,$oldType,$fy);
        $this->ensureCounter($leave->user_id,$leave->type,$fy);

        return redirect()->route('leaves.index')->with('update_success','Leave updated.');
    }

    public function destroy(Leave $leave)
    {
        $this->authorizeMutable($leave);

        $tz      = $this->resolvedTimezone();
        $fyStart = $this->fiscalStartMonth();
        $fy      = $this->fiscalYearOfDate(Carbon::parse($leave->start_date,$tz),$fyStart);
        $type    = $leave->type;
        $uid     = $leave->user_id;

        $leave->delete();
        $this->ensureCounter($uid,$type,$fy);

        return redirect()->route('leaves.index')->with('remove_success','Leave deleted.');
    }

    // ---------------- Admin Status Actions ----------------
    public function approve(Leave $leave)
    {
        $this->authorizeAdmin();
        $tz  = $this->resolvedTimezone();
        $end = Carbon::parse($leave->end_date,$tz);
        if ($end->lt(Carbon::today($tz))) return back()->withErrors(['status'=>'Past leave cannot be approved.']);

        if (strcasecmp($leave->status,'Approved')!==0) {
            $leave->update(['status'=>'Approved']);
            LeaveStatusHistory::create([
                'leave_id'=>$leave->id,'action'=>'Approved',
                'changed_by'=>Auth::id(),'occurred_at'=>now($tz),
            ]);
            $this->recalculateSingleCounter($leave);
        }
        return back()->with('global_update_success','Leave approved.');
    }

    public function reject(Leave $leave)
    {
        $this->authorizeAdmin();
        $tz  = $this->resolvedTimezone();
        $end = Carbon::parse($leave->end_date,$tz);
        if ($end->lt(Carbon::today($tz))) return back()->withErrors(['status'=>'Past leave cannot be rejected.']);

        if (strcasecmp($leave->status,'Rejected')!==0) {
            $leave->update(['status'=>'Rejected']);
            LeaveStatusHistory::create([
                'leave_id'=>$leave->id,'action'=>'Rejected',
                'changed_by'=>Auth::id(),'occurred_at'=>now($tz),
            ]);
            $this->recalculateSingleCounter($leave);
        }
        return back()->with('global_update_success','Leave rejected.');
    }

    public function pending(Leave $leave)
    {
        $this->authorizeAdmin();
        $tz  = $this->resolvedTimezone();
        $end = Carbon::parse($leave->end_date,$tz);
        if ($end->lt(Carbon::today($tz))) return back()->withErrors(['status'=>'Past leave cannot be changed.']);

        $cur = strtolower($leave->status ?? '');
        if (in_array($cur,['approved','rejected'])) {
            return back()->withErrors(['status'=>'Cannot revert Approved/Rejected to Pending.']);
        }
        if ($cur!=='pending') {
            $leave->update(['status'=>'Pending']);
            $this->recalculateSingleCounter($leave);
        }
        return back()->with('global_update_success','Leave set to Pending.');
    }

    // ---------------- Reset Logic ----------------
    public function resetCounters(Request $request)
    {
        $this->authorizeAdmin();

        $tz      = $this->resolvedTimezone();
        $fyStart = $this->fiscalStartMonth();
        $today   = now($tz);

        $mode = $request->input('year_mode','current'); // current | next | custom
        $targetFY = match($mode) {
            'next'   => $this->fiscalYearOfDate($today->copy()->addYear(),$fyStart),
            'custom' => (int)$request->input('year',$this->fiscalYearOfDate($today,$fyStart)),
            default  => $this->fiscalYearOfDate($today,$fyStart),
        };

        $types      = $this->leaveTypes();
        $allowances = config('leaves.allowances', []);
        $fallback   = (int) config('leaves.default_allowance', 5);
        $now        = now($tz);

        DB::transaction(function() use ($types,$allowances,$fallback,$targetFY,$now) {
            foreach (User::pluck('id') as $uid) {
                foreach ($types as $type) {
                    LeaveCounter::updateOrCreate(
                        ['user_id'=>$uid,'leave_type'=>$type,'year'=>$targetFY],
                        ['allowance'=>(int)($allowances[$type] ?? $fallback),'used'=>0,'updated_at'=>$now]
                    );
                    // Set new cutoff (exclude all older leaves from counting)
                    Cache::put($this->cutoffKey($uid,$targetFY,$type), $now->timestamp, $now->copy()->addYear());
                }
            }
        });

        session(['leaves_active_year'=>$targetFY]);

        return redirect()->route('leaves.index')
            ->with('success',"Leave counters reset for fiscal year {$targetFY}.");
    }

    // ---------------- Counter Helpers ----------------
    private function ensureAllCounters(int $userId,int $fiscalYear): void
    {
        foreach ($this->leaveTypes() as $type) {
            $this->ensureCounter($userId,$type,$fiscalYear);
        }
    }

    private function ensureCounter(int $userId,string $type,int $fiscalYear): LeaveCounter
    {
        $counter = LeaveCounter::firstOrCreate(
            ['user_id'=>$userId,'leave_type'=>$type,'year'=>$fiscalYear],
            ['allowance'=>$this->defaultAllowance($type),'used'=>0]
        );

        // Recompute used counting only leaves created AFTER last reset cutoff
        $counter->used = $this->computeUsedSinceCutoff($userId,$type,$fiscalYear);
        $counter->save();

        return $counter;
    }

    private function recalculateSingleCounter(Leave $leave): void
    {
        $tz      = $this->resolvedTimezone();
        $fyStart = $this->fiscalStartMonth();
        $fy      = $this->fiscalYearOfDate(Carbon::parse($leave->start_date,$tz),$fyStart);

        $counter = LeaveCounter::where([
            'user_id'=>$leave->user_id,
            'leave_type'=>$leave->type,
            'year'=>$fy,
        ])->first();

        if ($counter) {
            $counter->used = $this->computeUsedSinceCutoff($leave->user_id,$leave->type,$fy);
            $counter->save();
        }
    }

    private function computeUsedSinceCutoff(int $userId,string $type,int $fiscalYear): int
    {
        $tz           = $this->resolvedTimezone();
        $fyStartMonth = $this->fiscalStartMonth();
        [$periodStart,$periodEnd] = $this->fiscalPeriodBounds($fiscalYear,$fyStartMonth,$tz);

        $cutoffTs = Cache::get($this->cutoffKey($userId,$fiscalYear,$type), null);

        return Leave::where('user_id',$userId)
            ->where('type',$type)
            ->whereIn('status',['Pending','Approved'])
            ->whereDate('start_date','>=',$periodStart->toDateString())
            ->whereDate('start_date','<=',$periodEnd->toDateString())
            ->when($cutoffTs, fn($q)=>$q->where('created_at','>=',Carbon::createFromTimestamp($cutoffTs)))
            ->count();
    }

    private function cutoffKey(int $userId,int $year,string $type): string
    {
        return 'leave_reset_cutoff:' . $userId . ':' . $year . ':' . md5($type);
    }

    // ---------------- Calculations / Fiscal ----------------
    private function resolvedTimezone(): string
    {
        return (string)(Auth::user()->timezone ?? config('app.timezone','UTC'));
    }

    private function fiscalStartMonth(): int
    {
        $m = (int)config('leaves.fiscal_year_start_month',1);
        return min(max($m,1),12);
    }

    private function fiscalYearOfDate(Carbon $date,int $fyStartMonth): int
    {
        return ($date->month >= $fyStartMonth) ? $date->year : ($date->year - 1);
    }

    private function fiscalPeriodBounds(int $fyYear,int $fyStartMonth,string $tz): array
    {
        $start = Carbon::create($fyYear,$fyStartMonth,1,0,0,0,$tz)->startOfDay();
        $end   = (clone $start)->addYear()->subDay()->endOfDay();
        return [$start,$end];
    }

    private function defaultAllowance(string $type): int
    {
        $map = config('leaves.allowances',[]);
        $fallback = (int)config('leaves.default_allowance',5);
        return (int)($map[$type] ?? $fallback);
    }

    private function leaveTypes(): array
    {
        $types = config('leaves.types',[]);
        if (is_array($types) && $types) return $types;

        $map = config('leaves.allowances',[]);
        if ($map) return array_keys($map);

        return [
            'Bereavement Leave',
            'Emergency/Personal Leave',
            'Mandatory Leave',
            'Sick Leave',
            'Vacation Leave',
        ];
    }

    // ---------------- Authorization ----------------
    private function authorizeView(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave),403);
    }

    private function authorizeMutable(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave) && $this->isPending($leave),403);
    }

    private function isOwnerOrAdmin(Leave $leave): bool
    {
        $u = Auth::user();
        return $leave->user_id === $u->id || (bool)($u->is_admin ?? false);
    }

    private function isPending(Leave $leave): bool
    {
        return strcasecmp($leave->status ?? '','Pending')===0;
    }

    private function authorizeAdmin(): void
    {
        abort_unless((bool)(Auth::user()->is_admin ?? false),403);
    }

    // ---------------- Misc ----------------
    private function globalStatusMessage(string $status): string
    {
        return 'Leave status is set to ' . ucfirst(strtolower($status));
    }

    private function hasOverlappingLeave($userId, Carbon $start, Carbon $end, $ignoreId = null): bool
    {
        return Leave::where('user_id',$userId)
            ->whereIn('status',['Pending','Approved'])
            ->when($ignoreId, fn($q)=>$q->where('id','!=',$ignoreId))
            ->where(function($q) use ($start,$end){
                $q->whereDate('start_date','<=',$end->toDateString())
                  ->whereDate('end_date','>=',$start->toDateString());
            })
            ->exists();
    }
}