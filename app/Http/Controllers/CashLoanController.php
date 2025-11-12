<?php

namespace App\Http\Controllers;

use App\Models\CashLoan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CashLoanController extends Controller
{
    protected array $statuses = ['Pending','Approved','Rejected','Active','Fully Paid','Cancelled'];

    private function isPending(CashLoan $loan): bool
    {
        return strcasecmp((string)$loan->status, 'Pending') === 0;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $personalLoans = CashLoan::with('user')
            ->where('user_id', $user->id)
            ->orderByDesc('date_requested')
            ->orderByDesc('id')
            ->get();

        $globalLoans = collect();
        if ($user->is_admin) {
            $globalLoans = CashLoan::with('user')
                ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('user_id'), fn($q) => $q->where('user_id', (int)$request->input('user_id')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $q->where(function ($sub) use ($term) {
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

    public function store(Request $request)
    {
        $auth = Auth::user();

        $data = $request->validate([
            'user_id'        => ['nullable','exists:users,id'],
            'date_requested' => ['nullable','date'],
            'amount'         => ['required','numeric','min:0.01'],
            'type'           => ['required','string','max:100'],
            'status'         => [$auth->is_admin ? 'required' : 'sometimes','string','max:50', Rule::in($this->statuses)],
            'remarks'        => ['nullable','string'],
        ]);

        // Force today's date
        $data['date_requested'] = now()->toDateString();

        if (!$auth->is_admin) {
            $data['user_id'] = $auth->id;
            $data['status']  = 'Pending';
        } else {
            $data['user_id'] = $request->filled('user_id')
                ? (int)$request->input('user_id')
                : $auth->id;
        }

        CashLoan::create($data);

        return redirect()->route('cashloans.index')->with('create_success', 'Cash loan created.');
    }

    public function show(CashLoan $cashloan)
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $cashloan->user_id === $user->id), 403);

        return view('cashloans.show', ['loan' => $cashloan]);
    }

    public function edit(CashLoan $cashloan)
    {
        $user = Auth::user();

        // Only Pending loans editable (admin any pending, user own pending)
        $canEdit = $this->isPending($cashloan) && ($user->is_admin || $cashloan->user_id === $user->id);
        abort_unless($canEdit, 403);

        return view('cashloans.edit', [
            'loan'     => $cashloan,
            'statuses' => $user->is_admin ? $this->statuses : ['Pending'],
            'users'    => $user->is_admin
                ? User::orderBy('first_name')->get(['id','first_name','last_name','middle_name'])
                : collect(),
        ]);
    }

    public function update(Request $request, CashLoan $cashloan)
    {
        $user = Auth::user();

        if ($user->is_admin) {
            abort_unless($this->isPending($cashloan), 403);
            $data = $request->validate([
                'user_id'        => ['required','exists:users,id'],
                'date_requested' => ['required','date'],
                'amount'         => ['required','numeric','min:0.01'],
                'type'           => ['required','string','max:100'],
                'status'         => ['required','string','max:50', Rule::in($this->statuses)],
                'remarks'        => ['nullable','string'],
            ]);
        } else {
            abort_unless($cashloan->user_id === $user->id && $this->isPending($cashloan), 403);
            $data = $request->validate([
                'date_requested' => ['required','date'],
                'amount'         => ['required','numeric','min:0.01'],
                'type'           => ['required','string','max:100'],
                'remarks'        => ['nullable','string'],
            ]);
            $data['user_id'] = $cashloan->user_id;
            $data['status']  = 'Pending';
        }

        $cashloan->update($data);

        return redirect()->route('cashloans.index')->with('update_success', 'Cash loan updated.');
    }

    public function destroy(CashLoan $cashloan)
    {
        $user = Auth::user();
        $canDelete = $this->isPending($cashloan) && ($user->is_admin || $cashloan->user_id === $user->id);
        abort_unless($canDelete, 403);

        $cashloan->delete();

        return redirect()->route('cashloans.index')->with('remove_success', 'Cash loan deleted.');
    }

    public function approve(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Approved']);
        return back()->with('admin_update_success', 'Cash Loan status set to Approved');
    }

    public function reject(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Rejected']);
        return back()->with('admin_update_success', 'Cash Loan status set to Rejected');
    }

    public function pending(CashLoan $cashloan)
    {
        abort_unless(Auth::user()?->is_admin, 403);
        $cashloan->update(['status' => 'Pending']);
        return back()->with('admin_update_success', 'Cash Loan status set to Pending');
    }
}