<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\CashLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TimeLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
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

        $monthNow = (int) now()->format('n');
        $yearNow = (int) now()->format('Y');
        $defaultHalf = now()->day <= 15 ? 1 : 2;

        if ($request->filled('ps_period') && str_contains($request->ps_period, '|')) {
            [$selStart, $selEnd] = explode('|', $request->ps_period, 2);
        } else {
            $mm = sprintf('%02d', $monthNow);
            $endDay = Carbon::create($yearNow, $monthNow, 1)->endOfMonth()->day;
            $selStart = $defaultHalf === 1 ? "{$yearNow}-{$mm}-01" : "{$yearNow}-{$mm}-16";
            $selEnd = $defaultHalf === 1 ? "{$yearNow}-{$mm}-15" : "{$yearNow}-{$mm}-" . sprintf('%02d', $endDay);
        }

        $periodEmployees = TimeLog::query()
            ->whereBetween('date', [$selStart, $selEnd])
            ->where('status', 'Approved') // drop this if you want all statuses
            ->whereNull('payslip_id') // exclude logs already used
            ->select('user_id', DB::raw('SUM(hours) as hours'))
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn($r) => ['user' => $r->user, 'hours' => (float) $r->hours]);

        return view('payslip.index', [
            'payslips'    => $payslips,
            'allPayslips' => $allPayslips,
            'periodEmployees' => $periodEmployees,
        ])
            ->with(compact('monthNow', 'yearNow', 'defaultHalf'));
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
        \DB::transaction(function () use ($payslip) {
            \App\Models\TimeLog::where('payslip_id', $payslip->id)->update(['payslip_id' => null]);
            $payslip->delete();
        });

        return back()->with('success', 'Payslip deleted and related time logs detached.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'      => ['required','integer','exists:users,id'],
            'period_from'  => ['required','date'],
            'period_to'    => ['required','date','after_or_equal:period_from'],
            'issue_date'   => ['nullable','date'],
            'hours_worked' => ['required','numeric','min:0'],
            'hourly_rate'  => ['required','numeric','min:0'],
            'gross_pay'    => ['required','numeric','min:0'],
            'adjustments'  => ['nullable','numeric'],
            'net_pay'      => ['required','numeric'],
        ]);

        // Guard: ensure there are unpaid logs to attach
        $hasUnattached = \App\Models\TimeLog::where('user_id', $data['user_id'])
            ->whereBetween('date', [$data['period_from'], $data['period_to']])
            ->whereNull('payslip_id')
            ->exists();

        if (!$hasUnattached) {
            return back()->withErrors(['user_id' => 'No available time logs for this period (already attached or none).'])->withInput();
        }

        $payslip = null;

        \DB::transaction(function () use (&$payslip, $data) {
            $payslip = new \App\Models\Payslip($data);
            $payslip->created_by = auth()->id();
            $payslip->issue_date = $payslip->issue_date ?: now();
            $payslip->save();

            // Attach all matching logs to this payslip
            \App\Models\TimeLog::where('user_id', $data['user_id'])
                ->whereBetween('date', [$data['period_from'], $data['period_to']])
                ->whereNull('payslip_id')
                ->update(['payslip_id' => $payslip->id]);
        });

        return redirect()->route('payslip.index', $payslip)->with('success', 'Payslip created.');
    }
}