<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function index()
        {
            $payslips = auth()->user()->payslips()->latest()->get();
            $users = null;
            $allPayslips = null;

            if (Auth::user() && Auth::user()->is_admin) {
                $users = User::orderBy('id')->get();
                $allPayslips = Payslip::with('user')->latest()->get();
            }
            
            return view('payslip.index', compact('payslips', 'users', 'allPayslips'));
        }

    // admin manage (shows form + employees)
    public function manage()
    {
        $users = User::orderBy('name')->get();
        return view('payslip.partials.admin', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'period_from'   => 'required|date',
            'period_to'     => 'required|date|after_or_equal:period_from',
            'hours_worked'  => 'required|numeric|min:0',
            'hourly_rate'   => 'required|numeric|min:0',
            'adjustments'   => 'nullable|numeric',
        ]);

        $gross = ($data['hours_worked'] ?? 0) * ($data['hourly_rate'] ?? 0);
        $adjustments = $data['adjustments'] ?? 0;
        $net = max(0, $gross + $adjustments);

        $payslip = Payslip::create([
            'user_id'       => $data['user_id'],
            'period_from'   => $data['period_from'],
            'period_to'     => $data['period_to'],
            'hours_worked'  => $data['hours_worked'] ?? 0,
            'hourly_rate'   => $data['hourly_rate'] ?? 0,
            'gross_pay'     => $gross,
            'adjustments'   => $data['adjustments'] ?? 0,
            'net_pay'       => $net,
            'issue_date'    => now(),
        ]);

        return redirect()->back()->with('success', 'Payslip issued successfully.');
    }

    public function show(Payslip $payslip)
    {
        if (! Auth::user()->is_admin && Auth::id() !== $payslip->user_id) {
            abort(403);
        }

        return view('payslip.show', compact('payslip'));
    }

    public function calculateHours(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $hours = (float) TimeLog::where('user_id', $data['user_id'])
            ->whereBetween('date', [$data['period_from'], $data['period_to']])
            ->sum('hours');

        $rate = isset($data['hourly_rate']) && $data['hourly_rate'] !== ''
            ? (float) $data['hourly_rate']
            : optional(User::find($data['user_id']))->hourly_rate ?? 0;

        $gross = round($hours * $rate, 2);

        return response()->json(['hours' => round($hours, 2), 'gross' => $gross]);
    }
}