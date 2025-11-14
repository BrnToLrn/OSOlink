<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // List leaves (users see their own; admins also see global list)
    public function index(Request $request)
    {
        $user = Auth::user();

        $personalLeaves = Leave::with('user')
            ->where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'personal_page');

        $globalLeaves = null;
        if ($user->is_admin ?? false) {
            $globalLeaves = Leave::with('user')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'global_page')
                ->appends($request->except('personal_page'));
        }

        return view('leaves.index', compact('personalLeaves', 'globalLeaves'));
    }

    public function create()
    {
        return view('leaves.create');
    }

    public function store(Request $request)
    {
        // Enforce today's date for start_date before validation
        $request->merge([
            'start_date' => now()->toDateString(),
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

        return redirect()->route('leaves.index')->with('create_success', 'Leave request created.');
    }

    public function show(Leave $leave)
    {
        $this->authorizeAccess($leave);
        return view('leaves.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        $this->authorizeAccess($leave);
        return view('leaves.edit', compact('leave'));
    }

    public function update(Request $request, Leave $leave)
    {
        $this->authorizeAccess($leave);

        // Users cannot change status via form; admins can
        $rules = [
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'type'       => ['required','string','max:100'],
            'reason'     => ['nullable','string'],
        ];
        if (Auth::user()->is_admin ?? false) {
            $rules['status'] = ['required','string','in:Pending,Approved,Rejected'];
        }

        $data = $request->validate($rules);

        if (!($this->isAdmin())) {
            // Force status back to Pending for regular users
            $data['status'] = 'Pending';
        }

        $leave->update($data);

        return redirect()->route('leaves.index')->with('update_success', 'Leave updated.');
    }

    public function destroy(Leave $leave)
    {
        $this->authorizeAccess($leave);
        $leave->delete();

        return redirect()->route('leaves.index')->with('remove_success', 'Leave deleted.');
    }

    // Admin actions
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

    // Helpers
    private function authorizeAccess(Leave $leave): void
    {
        $user = Auth::user();
        if (!($user->is_admin ?? false) && $leave->user_id !== $user->id) {
            abort(403);
        }
    }

    private function authorizeAdmin(): void
    {
        if (!$this->isAdmin()) {
            abort(403);
        }
    }

    private function isAdmin(): bool
    {
        return (bool)(Auth::user()->is_admin ?? false);
    }
}