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
    public function index(Request $request)
    {
        // default - current user's payslips
        $payslips = auth()->user()->payslips()->latest()->get();
        $users = null;
        $allPayslips = null;

        // Admin: build filtered query based on GET params
        if (Auth::user() && Auth::user()->is_admin) {
            $users = User::orderBy('id')->get();

            $q = Payslip::with('user');

            // search by employee name or email
            if ($search = $request->query('search')) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->whereRaw(
                        "concat(first_name, ' ', coalesce(middle_name, ''), ' ', last_name) LIKE ?",
                        ["%{$search}%"]
                    )->orWhere('email', 'like', "%{$search}%");
                });
            }

            // date range on issue_date
            if ($from = $request->query('from')) {
                $q->whereDate('issue_date', '>=', $from);
            }
            if ($to = $request->query('to')) {
                $q->whereDate('issue_date', '<=', $to);
            }

            // safe sort handling
            $allowedSorts = ['employee', 'issue_date', 'period_from', 'net_pay'];
            $sort = $request->query('sort', 'issue_date');
            $order = $request->query('order', 'desc') === 'asc' ? 'asc' : 'desc';

            if ($sort === 'employee') {
                // join users to sort by name
                $q->join('users', 'payslips.user_id', '=', 'users.id')
                  ->select('payslips.*')
                  ->orderBy('users.last_name', $order)
                  ->orderBy('users.first_name', $order);
            } elseif (in_array($sort, $allowedSorts)) {
                $q->orderBy($sort, $order);
            } else {
                $q->orderBy('issue_date', $order);
            }

            // fetch results (use paginate(...) if you want pagination)
            $allPayslips = $q->get();
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
            ->where('status', 'Approved')
            ->whereBetween('date', [$data['period_from'], $data['period_to']])
            ->sum('hours');

        $rate = isset($data['hourly_rate']) && $data['hourly_rate'] !== ''
            ? (float) $data['hourly_rate']
            : optional(User::find($data['user_id']))->hourly_rate ?? 0;

        $gross = round($hours * $rate, 2);

        return response()->json(['hours' => round($hours, 2), 'gross' => $gross]);
    }
}