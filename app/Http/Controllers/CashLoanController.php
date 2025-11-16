<?php

namespace App\Http\Controllers;

use App\Models\CashLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashLoanController extends Controller
{
    // App statuses
    private array $statuses      = ['Pending','Approved','Rejected','Active','Fully Paid','Cancelled'];
    // When in these states, edits/deletes and admin approval/rejection are disabled
    private array $lockedStatus  = ['Active','Fully Paid'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function isPending(CashLoan $loan): bool
    {
        return strcasecmp((string)$loan->status, 'Pending') === 0;
    }

    private function isLocked(CashLoan $loan): bool
    {
        return in_array((string)$loan->status, $this->lockedStatus, true);
    }

    private function userHasOngoing(int $userId): bool
    {
        // Ongoing == Active
        return CashLoan::where('user_id', $userId)->where('status', 'Active')->exists();
    }

    // List personal + (if admin) global loans
    public function index(Request $request)
    {
        $user = Auth::user();

        $personalLoans = CashLoan::with('user')
            ->where('user_id', $user->id)
            ->orderByDesc('date_requested')
            ->orderByDesc('id')
            ->get();

        $hasOngoing = $this->userHasOngoing($user->id);

        $globalLoans = collect();
        if ($user->is_admin ?? false) {
            $globalLoans = CashLoan::with('user')
                ->when($request->filled('status'), function ($q) use ($request) {
                    $st = ucfirst(strtolower($request->string('status')));
                    $q->where('status', $st);
                })
                ->orderByDesc('date_requested')
                ->orderByDesc('id')
                ->get();
        }

        return view('cashloans.index', [
            'personalLoans' => $personalLoans,
            'globalLoans'   => $globalLoans,
            'loans'         => $personalLoans,
            'hasOngoing'    => $hasOngoing,
            'statuses'      => $this->statuses,
        ]);
    }

    // Create form (blocked if user has ongoing/Active loan)
    public function create()
    {
        $user = Auth::user();

        if (!($user->is_admin ?? false) && $this->userHasOngoing($user->id)) {
            return redirect()->route('cashloans.index')
                ->with('blocked_create', 'You have an ongoing cash loan. Request a new one after it is fully paid.');
        }

        return view('cashloans.create');
    }

    // Store new loan (uses browser date to avoid timezone drift)
    public function store(Request $request)
    {
        $auth = Auth::user();

        if (!($auth->is_admin ?? false) && $this->userHasOngoing($auth->id)) {
            return redirect()->route('cashloans.index')
                ->with('blocked_create', 'You have an ongoing cash loan. Request a new one after it is fully paid.');
        }

        $data = $request->validate([
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'pay_periods'    => ['required','integer','min:1','max:6'],
            'remarks'        => ['nullable','string'],
        ]);

        $loan = new CashLoan($data);
        $loan->user_id = $auth->id;
        $loan->status  = 'Pending';
        $loan->save();

        return redirect()->route('cashloans.index')->with('create_success', 'Cash loan request filed.');
    }

    // View single loan
    public function show(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $cashloan->user_id === $user->id, 403);

        return view('cashloans.show', ['loan' => $cashloan]);
    }

    // Edit form (only Pending, for owner or admin)
    public function edit(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        return view('cashloans.edit', ['loan' => $cashloan]);
    }

    // Update (only Pending)
    public function update(Request $request, CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        $data = $request->validate([
            'date_requested' => ['required','date'], // readonly in UI, kept for consistency
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'pay_periods'    => ['required','integer','min:1','max:6'],
            'remarks'        => ['nullable','string'],
        ]);

        $cashloan->update($data);

        return redirect()->route('cashloans.index')->with('update_success', 'Cash loan updated.');
    }

    // Delete (only Pending)
    public function destroy(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        $cashloan->delete();

        return redirect()->route('cashloans.index')->with('remove_success', 'Cash loan deleted.');
    }

    // Admin status changes (disabled for Active/Fully Paid)
    public function approve(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_if($this->isLocked($cashloan), 403, 'Cannot modify a paid or ongoing loan.');
        $cashloan->update(['status' => 'Approved']);
        return back()->with('admin_update_success', 'Cash loan set to Approved.');
    }

    public function reject(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_if($this->isLocked($cashloan), 403, 'Cannot modify a paid or ongoing loan.');
        $cashloan->update(['status' => 'Rejected']);
        return back()->with('admin_update_success', 'Cash loan set to Rejected.');
    }

    public function pending(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_if($this->isLocked($cashloan), 403, 'Cannot modify a paid or ongoing loan.');
        $cashloan->update(['status' => 'Pending']);
        return back()->with('admin_update_success', 'Cash loan set to Pending.');
    }
}