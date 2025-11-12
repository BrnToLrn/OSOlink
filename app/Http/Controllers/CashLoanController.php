<?php

namespace App\Http\Controllers;

use App\Models\CashLoan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CashLoanController extends Controller
{
    /**
     * All valid statuses used across the system.
     */
    protected array $statuses = [
        'Pending', 'Approved', 'Rejected', 'Active', 'Fully Paid', 'Cancelled'
    ];

    /**
     * Display personal and global (admin) cash loans.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Personal (user) loans
        $personalLoans = CashLoan::with('user')
            ->where('user_id', $user->id)
            ->orderByDesc('date_requested')
            ->orderByDesc('id')
            ->get();

        // Global (admin) loans with filters
        $globalLoans = collect();
        if ($user->is_admin) {
            $globalLoans = CashLoan::with('user')
                ->when($request->filled('status'), fn($q) =>
                    $q->where('status', $request->string('status')))
                ->when($request->filled('user_id'), fn($q) =>
                    $q->where('user_id', (int)$request->input('user_id')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $q->where(function($sub) use ($term) {
                        $sub->whereHas('user', function($uq) use ($term) {
                                $uq->where('first_name','ILIKE',$term)
                                   ->orWhere('last_name','ILIKE',$term)
                                   ->orWhere('middle_name','ILIKE',$term);
                            })
                            ->orWhere('remarks','ILIKE',$term)
                            ->orWhere('type','ILIKE',$term);
                    });
                })
                ->orderByDesc('date_requested')
                ->orderByDesc('id')
                ->get();
        }

        return view('cashloans.index', [
            'personalLoans' => $personalLoans,
            'globalLoans'   => $globalLoans,
            'loans'         => $personalLoans,
            'statuses'      => $this->statuses,
        ]);
    }

    /**
     * Show the form for creating a new loan.
     */
    public function create()
    {
        $user = Auth::user();

        return view('cashloans.create', [
            'statuses' => $user->is_admin ? $this->statuses : ['Pending'],
            'users'    => $user->is_admin
                ? User::orderBy('first_name')->get(['id','first_name','last_name','middle_name'])
                : collect(),
        ]);
    }

    /**
     * Store a newly created loan.
     * Redirects back to index with a "create_success" flash so the message
     * appears beside the "+ REQUEST CASH LOAN" button (like Leaves).
     */
    public function store(Request $request)
    {
        $auth = Auth::user();

        $data = $request->validate([
            'user_id'        => ['nullable','exists:users,id'],
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'status'         => [$auth->is_admin ? 'required' : 'sometimes','string','max:50', Rule::in($this->statuses)],
            'remarks'        => ['nullable','string'],
        ]);

        if (!$auth->is_admin) {
            $data['user_id'] = $auth->id;
            $data['status']  = 'Pending';
        } else {
            // Default to admin if no user was selected
            $data['user_id'] = $request->filled('user_id') ? (int)$request->input('user_id') : $auth->id;
        }

        CashLoan::create($data);

        return redirect()
            ->route('cashloans.index')
            ->with('create_success', 'Cash loan created.');
    }

    /**
     * Display a single loan record.
     */
    public function show(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $cashloan->user_id === $user->id), 403);

        return view('cashloans.show', ['loan' => $cashloan]);
    }

    /**
     * Edit loan (admin only).
     */
    public function edit(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        return view('cashloans.edit', [
            'loan'     => $cashloan,
            'statuses' => $this->statuses,
            'users'    => User::orderBy('first_name')->get(['id','first_name','last_name','middle_name']),
        ]);
    }

    /**
     * Update loan (admin only).
     * Redirect to index with "update_success" to mirror Leaves page UX.
     */
    public function update(Request $request, CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $data = $request->validate([
            'user_id'        => ['required','exists:users,id'],
            'date_requested' => ['required','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'status'         => ['required','string','max:50', Rule::in($this->statuses)],
            'remarks'        => ['nullable','string'],
        ]);

        $cashloan->update($data);

        return redirect()
            ->route('cashloans.index')
            ->with('update_success', 'Cash loan updated.');
    }

    /**
     * Delete a loan (admin only).
     * Redirect to index with "remove_success" to mirror Leaves page UX.
     */
    public function destroy(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $cashloan->delete();

        return redirect()
            ->route('cashloans.index')
            ->with('remove_success', 'Cash loan deleted.');
    }

    /**
     * Set loan status to Approved.
     */
    public function approve(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Approved']);

        return back()->with('admin_update_success', 'Cash Loan status set to Approved');
    }

    /**
     * Set loan status to Rejected.
     */
    public function reject(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Rejected']);

        return back()->with('admin_update_success', 'Cash Loan status set to Rejected');
    }

    /**
     * Set loan status to Pending.
     */
    public function pending(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Pending']);

        return back()->with('admin_update_success', 'Cash Loan status set to Pending');
    }
}