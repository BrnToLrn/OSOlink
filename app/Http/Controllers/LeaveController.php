<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $personalLeaves = Leave::where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $globalLeaves = null;
        if ($user->is_admin ?? false) {
            $globalLeaves = Leave::with('user')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        }

        return view('leaves.index', compact('personalLeaves', 'globalLeaves'));
    }

    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        // Force start_date = "today" in the correct timezone so midnight rolls over properly
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

        $data['user_id'] = Auth::id();
        Leave::create($data);

        return redirect()->route('leaves.index')
            ->with('create_success', 'Leave request created.');
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

        // Keep start_date fixed to "today" in the resolved timezone
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

        // Users cannot flip status here; admin pages handle approvals
        if (!$this->isAdmin()) {
            $data['status'] = 'Pending';
        }

        $leave->update($data);

        return redirect()->route('leaves.index')
            ->with('update_success', 'Leave updated.');
    }

    public function destroy(Leave $leave)
    {
        $this->authorizeMutable($leave);
        $leave->delete();

        return redirect()->route('leaves.index')
            ->with('remove_success', 'Leave deleted.');
    }

    public function approve(Leave $leave)
    {
        $this->authorizeAdmin();
        $leave->update(['status' => 'Approved']);
        return back()->with('admin_update_success', 'Leave status set to Approved.');
    }

    public function reject(Leave $leave)
    {
        $this->authorizeAdmin();
        $leave->update(['status' => 'Rejected']);
        return back()->with('admin_update_success', 'Leave status set to Rejected.');
    }

    public function pending(Leave $leave)
    {
        $this->authorizeAdmin();
        $leave->update(['status' => 'Pending']);
        return back()->with('admin_update_success', 'Leave status set to Pending.');
    }

    // ---- Helpers ----

    private function resolvedTimezone(): string
    {
        // Prefer per-user timezone if your users table has it; otherwise use app timezone
        return (string) (Auth::user()->timezone ?? config('app.timezone', 'UTC'));
    }

    private function isAdmin(): bool
    {
        return (bool)(Auth::user()->is_admin ?? false);
    }

    private function isOwnerOrAdmin(Leave $leave): bool
    {
        $user = Auth::user();
        return $leave->user_id === $user->id || ($user->is_admin ?? false);
    }

    private function isPending(Leave $leave): bool
    {
        return strcasecmp((string)($leave->status ?? ''), 'Pending') === 0;
    }

    private function authorizeView(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave), 403);
    }

    private function authorizeMutable(Leave $leave): void
    {
        abort_unless($this->isOwnerOrAdmin($leave) && $this->isPending($leave), 403);
    }

    private function authorizeAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }
}