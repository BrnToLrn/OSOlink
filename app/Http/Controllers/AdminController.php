<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\HourlyRateHistory;
use App\Models\Payroll; // <--- add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ['%' . $request->search . '%'])
                ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status (active/inactive)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting (default: name asc)
        $sortField = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');

        if ($sortField === 'name') {
            $query->orderBy('first_name', $sortOrder)->orderBy('last_name', $sortOrder);
        } elseif (in_array($sortField, ['email', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        // Logs
        $logQuery = AuditLog::with('user');

        if ($request->filled('log_user')) {
            $logQuery->whereHas('user', function ($q) use ($request) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$request->log_user}%"]);
            });
        }

        if ($request->filled('log_action')) {
            $logQuery->where('action', 'like', "%{$request->log_action}%");
        }

        $logs = $logQuery
            ->orderBy($request->get('log_sort', 'created_at'), $request->get('log_order', 'desc'))
            ->paginate(10, ['*'], 'logs_page');

        $users = $query->paginate(10)->withQueryString();
        $payrolls = Payroll::orderBy('created_at', 'desc')->get(); // add this

        return view('adminpanel.admin', compact('users', 'logs', 'payrolls')); // pass $payrolls
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'job_type' => 'required|string|max:255',
            'hourly_rate' => 'required|numeric|min:0',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'job_type' => $validated['job_type'],
            'hourly_rate' => $validated['hourly_rate'],
            'password' => bcrypt($validated['password']),
            'is_admin' => $request->has('is_admin'),
        ]);

        $this->logAction('Created User', $user);

        return redirect()->route('adminpanel.admin')->with('create_success', 'User created successfully!');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('adminpanel.admin')->with('selfdeactivation', 'You cannot deactivate your own account!');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $this->logAction($user->is_active ? 'Activated User' : 'Deactivated User', $user);

        return redirect()->route('adminpanel.admin')->with('toggle_success', 'User status updated!');
    }

    protected function logAction($action, $target = null, $changes = null)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target->id ?? null,
            'changes' => $changes,
        ]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        $rateHistory = HourlyRateHistory::with('changer')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('adminpanel.show', compact('user', 'rateHistory'));
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldRate = $user->hourly_rate;

        $validated = $request->validate([
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'job_type' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_admin' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // normalize boolean checkbox
        $validated['is_admin'] = $request->has('is_admin');

        // update only allowed fields
        $user->bank_name = $validated['bank_name'] ?? $user->bank_name;
        $user->bank_account_number = $validated['bank_account_number'] ?? $user->bank_account_number;
        $user->job_type = $validated['job_type'] ?? $user->job_type;
        // allow null/empty to remain unchanged if you prefer; here we set if present
        if ($request->filled('hourly_rate') || $request->hourly_rate === '0' || $request->hourly_rate === 0) {
            $user->hourly_rate = $validated['hourly_rate'];
        }
        $user->is_admin = $validated['is_admin'];
        $user->save();

        // record hourly rate history when changed
        $newRate = $user->hourly_rate;
        if ($oldRate != $newRate) {
            HourlyRateHistory::create([
                'user_id' => $user->id,
                'changed_by' => auth()->id(),
                'old_rate' => $oldRate,
                'new_rate' => $newRate,
            ]);
        }

        $this->logAction('Updated User', $user, ['updated_fields' => array_keys($validated)]);

        return redirect()->back()->with('update_success', 'User updated.');
    }
}
