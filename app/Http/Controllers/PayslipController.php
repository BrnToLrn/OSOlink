<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function index()
        {
            $payslips = auth()->user()->payslips()->latest()->get();
            $users = null;

            if (Auth::user() && Auth::user()->is_admin) {
                $users = User::orderBy('id')->get();
            }
            
            return view('payslip.index', compact('payslips', 'users'));
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
                'user_id' => 'required|exists:users,id',
                'period_from' => 'required|date',
                'period_to' => 'required|date|after_or_equal:period_from',
                'hours_worked' => 'nullable|numeric|min:0',
                'hourly_rate' => 'nullable|numeric|min:0',
                'job_type' => 'nullable|string',
                'deductions' => 'nullable|numeric|min:0',
            ]);

            $gross = ($data['hours_worked'] ?? 0) * ($data['hourly_rate'] ?? 0);
            $deductions = $data['deductions'] ?? 0;
            $net = max(0, $gross - $deductions);

            $payslip = Payslip::create([
                'user_id' => $data['user_id'],
                'period_from' => $data['period_from'],
                'period_to' => $data['period_to'],
                'job_type' => $data['job_type'],
                'hours_worked' => $data['hours_worked'] ?? 0,
                'hourly_rate' => $data['hourly_rate'] ?? 0,
                'gross_pay' => $gross,
                'deductions' => $deductions,
                'net_pay' => $net,
                'issued_at' => now(),
                'status' => 'issued',
            ]);

            return redirect()->back()->with('success', 'Payslip added.');
        }

        public function show(Payslip $payslip)
        {
            if (! Auth::user()->is_admin && Auth::id() !== $payslip->user_id) {
                abort(403);
            }

            return view('payslip.show', compact('payslip'));
        }
}