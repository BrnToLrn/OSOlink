<?php

namespace App\Http\Controllers;

use App\Models\CashLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashLoanController extends Controller
{
    private array $statuses = ['Pending','Approved','Rejected','Active','Fully Paid','Cancelled'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function isPending(CashLoan $loan): bool
    {
        return strcasecmp((string)$loan->status, 'Pending') === 0;
    }

    private function approvalLocked(CashLoan $loan): bool
    {
        return in_array((string)$loan->status, ['Active','Fully Paid'], true);
    }

    private function userHasOngoing(int $userId, ?int $ignoreId = null): bool
    {
        return CashLoan::where('user_id', $userId)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('status', 'Active')
            ->exists();
    }

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
                    $map = [
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                        'fully paid' => 'Fully Paid',
                        'cancelled' => 'Cancelled',
                    ];
                    $key = strtolower((string)$request->get('status'));
                    if (isset($map[$key])) {
                        $q->where('status', $map[$key]);
                    }
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

    public function create()
    {
        $user = Auth::user();

        if (!($user->is_admin ?? false) && $this->userHasOngoing($user->id)) {
            return redirect()->route('cashloans.index')
                ->with('blocked_create', 'You have an ongoing cash loan. Request a new one after it is fully paid.');
        }

        return view('cashloans.create');
    }

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

    public function show(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $cashloan->user_id === $user->id, 403);

        return view('cashloans.show', ['loan' => $cashloan]);
    }

    public function edit(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        return view('cashloans.edit', ['loan' => $cashloan]);
    }

    public function update(Request $request, CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        $data = $request->validate([
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'pay_periods'    => ['required','integer','min:1','max:6'],
            'remarks'        => ['nullable','string'],
        ]);

        $cashloan->update($data);

        return redirect()->route('cashloans.index')->with('update_success', 'Cash loan updated.');
    }

    public function destroy(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($this->isPending($cashloan) && (($user->is_admin ?? false) || $cashloan->user_id === $user->id), 403);

        $cashloan->delete();

        return redirect()->route('cashloans.index')->with('remove_success', 'Cash loan deleted.');
    }

    // Admin approval set Approved
    public function approve(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_if($this->approvalLocked($cashloan), 403, 'Cannot approve: ongoing or paid.');
        $cashloan->update(['status' => 'Approved']);
        return back()->with('admin_update_success', 'Loan approved.');
    }

    // Admin rejection
    public function reject(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_if($this->approvalLocked($cashloan), 403, 'Cannot reject: ongoing or paid.');
        $cashloan->update(['status' => 'Rejected']);
        return back()->with('admin_update_success', 'Loan rejected.');
    }

    // Mark Active
    public function activate(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if ($this->userHasOngoing($cashloan->user_id, $cashloan->id)) {
            return back()->with('admin_update_error', 'User already has ongoing loan.');
        }

        if (!in_array($cashloan->status, ['Pending','Approved'], true)) {
            return back()->with('admin_update_error', 'Only Pending or Approved can become Ongoing.');
        }

        $cashloan->update(['status' => 'Active']);
        return back()->with('admin_update_success', 'Loan marked Ongoing.');
    }

    // Mark Fully Paid
    public function paid(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        if ($cashloan->status === 'Fully Paid') {
            return back()->with('admin_update_success', 'Already Fully Paid.');
        }

        if (!in_array($cashloan->status, ['Active','Approved'], true)) {
            return back()->with('admin_update_error', 'Only Active or Approved can be paid.');
        }

        $cashloan->update(['status' => 'Fully Paid']);
        return back()->with('admin_update_success', 'Loan marked Fully Paid.');
    }
}