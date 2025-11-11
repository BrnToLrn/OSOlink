<?php

namespace App\Http\Controllers;

use App\Models\CashLoan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashLoanController extends Controller
{
    protected array $statuses = ['Pending','Approved','Rejected','Active','Fully Paid','Cancelled'];

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = CashLoan::with('user')
            ->when(!$user->is_admin, fn($q) => $q->where('user_id', $user->id))
            ->when($user->is_admin && $request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($user->is_admin && $request->filled('user_id'), fn($q) => $q->where('user_id', $request->integer('user_id')))
            ->orderByDesc('date_requested')->orderByDesc('id');

        $loans = $query->paginate(15)->withQueryString();

        return view('cashloans.index', [
            'loans'    => $loans,
            'statuses' => $this->statuses,
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        return view('cashloans.create', [
            'statuses' => $user->is_admin ? $this->statuses : ['Pending'],
            'users'    => $user->is_admin ? User::orderBy('first_name')->get(['id','first_name','last_name']) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $auth = Auth::user();

        $data = $request->validate([
            'user_id'        => [$auth->is_admin ? 'required' : 'sometimes', 'exists:users,id'],
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'status'         => [$auth->is_admin ? 'required' : 'sometimes','string','max:50'],
            'remarks'        => ['nullable','string'],
        ]);

        if (!$auth->is_admin) {
            $data['user_id'] = $auth->id;
            $data['status']  = 'Pending';
        }

        $loan = CashLoan::create($data);

        return redirect()->route('cashloans.show', $loan)->with('success', 'Cash loan created.');
    }

    public function show(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $cashloan->user_id === $user->id), 403);

        return view('cashloans.show', ['loan' => $cashloan]);
    }

    public function edit(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        return view('cashloans.edit', [
            'loan'     => $cashloan,
            'statuses' => $this->statuses,
            'users'    => User::orderBy('first_name')->get(['id','first_name','last_name']),
        ]);
    }

    public function update(Request $request, CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $data = $request->validate([
            'user_id'        => ['required','exists:users,id'],
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'status'         => ['required','string','max:50'],
            'remarks'        => ['nullable','string'],
        ]);

        $cashloan->update($data);

        return redirect()->route('cashloans.show', $cashloan)->with('success', 'Cash loan updated.');
    }

    public function destroy(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $cashloan->delete();

        return redirect()->route('cashloans.index')->with('success', 'Cash loan deleted.');
    }

    // Admin custom actions to match routes
    public function activate(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Active']);
        return back()->with('success', 'Loan activated.');
    }

    public function markPaid(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Fully Paid']);
        return back()->with('success', 'Loan marked as fully paid.');
    }

    public function cancel(Request $request, CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $reason = (string) $request->input('reason', '');
        $remarks = trim(($cashloan->remarks ? $cashloan->remarks."\n" : '').($reason ? "Cancelled: $reason" : 'Cancelled.'));
        $cashloan->update(['status' => 'Cancelled', 'remarks' => $remarks]);
        return back()->with('success', 'Loan cancelled.');
    }
}