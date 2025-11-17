<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\CashLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->is_admin ?? false) {
            $allPayslips = Payslip::with('user')
                ->orderByDesc('issue_date')
                ->orderByDesc('id')
                ->paginate(25);

            $payslips = Payslip::where('user_id', $user->id)
                ->orderByDesc('issue_date')
                ->orderByDesc('id')
                ->paginate(10);
        } else {
            $payslips = Payslip::where('user_id', $user->id)
                ->orderByDesc('issue_date')
                ->orderByDesc('id')
                ->paginate(15);
            $allPayslips = collect();
        }

        return view('payslip.index', [
            'payslips'    => $payslips,
            'allPayslips' => $allPayslips,
        ]);
    }

    public function show(Payslip $payslip)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $payslip->user_id === $user->id, 403);
        return view('payslip.show', compact('payslip'));
    }

    public function edit(Payslip $payslip)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $payslip->user_id === $user->id, 403);
        abort_if($payslip->is_paid, 403, 'Paid payslips cannot be edited.');
        return view('payslip.edit', compact('payslip'));
    }

    public function update(Request $request, Payslip $payslip)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $payslip->user_id === $user->id, 403);
        abort_if($payslip->is_paid, 403, 'Paid payslips cannot be edited.');

        $data = $request->validate([
            'period_from' => ['required','date'],
            'period_to'   => ['required','date','after_or_equal:period_from'],
            'issue_date'  => ['required','date'],
            'hours_worked' => ['required','numeric','min:0'],
            'hourly_rate'  => ['required','numeric','min:0'],
            'adjustments'  => ['nullable','numeric'],
            'is_paid'      => ['sometimes','boolean'],
            'cash_loan_id'            => ['nullable','integer','exists:cash_loans,id'],
            'cash_loan_period_number' => ['nullable','integer','min:1','max:255'],
        ]);

        $data['is_paid'] = (bool)($data['is_paid'] ?? false);

        $payslip->fill($data);

        if (!empty($data['cash_loan_id']) && !empty($data['cash_loan_period_number'])) {
            $loan = CashLoan::find($data['cash_loan_id']);
            if ($loan) {
                $payslip->setLoanInstallment($loan, (int)$data['cash_loan_period_number']);
            }
        }

        $payslip->recomputeTotals()->save();

        return redirect()->route('payslip.show', $payslip)->with('update_success', 'Payslip updated.');
    }

    public function destroy(Payslip $payslip)
    {
        $user = Auth::user();
        abort_unless(($user->is_admin ?? false) || $payslip->user_id === $user->id, 403);
        abort_if($payslip->is_paid, 403, 'Paid payslips cannot be deleted.');
        $payslip->delete();
        return redirect()->route('payslip.index')->with('remove_success', 'Payslip deleted.');
    }
}