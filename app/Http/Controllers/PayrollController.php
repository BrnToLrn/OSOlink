<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function batchCreate(Request $request)
    {
        // 1. Validate the form data from the modal
        $validated = $request->validate([
            'period_from' => 'required|date',
            'period_to'   => 'required|date|after_or_equal:period_from',
        ]);

        // enforce "payroll period" semantics: same month/year and either 1..15 or 16..end-of-month
        $from = Carbon::parse($validated['period_from']);
        $to   = Carbon::parse($validated['period_to']);

        if ($from->year !== $to->year || $from->month !== $to->month) {
            return back()->with('error', 'Payroll period must be within the same month and year.');
        }

        $lastDay = $from->copy()->endOfMonth()->day;
        $okFirstHalf  = ($from->day === 1 && $to->day === 15);
        $okSecondHalf = ($from->day === 16 && $to->day === $lastDay);
        if (!($okFirstHalf || $okSecondHalf)) {
            return back()->with('error', 'Payroll period must be either 1–15 or 16–' . $lastDay . ' of the same month.');
        }

        try {
            // 2. Start a Database Transaction
            $payroll = DB::transaction(function () use ($validated) {

                // 3. Find all payslips that are not in a batch yet AND
                //    match the date range.
                //
                $payslipsToBatch = Payslip::whereNull('payroll_id')
                    ->whereBetween('issue_date', [
                        $validated['period_from'],
                        $validated['period_to']
                    ])
                    ->get();

                // 4. Check if we found any payslips
                if ($payslipsToBatch->isEmpty()) {
                    // We throw an exception to roll back the transaction
                    // and show an error.
                    throw new \Exception('No unassigned payslips were found for that period.');
                }

                // 5. Calculate the total amount
                // include cash loan period deduction if present on the payslip
                // (expects a numeric column like `cash_loan_period_deduction` on payslips;
                //  fallback to 0 when absent)
                $totalAmount = $payslipsToBatch->sum(function ($s) {
                    $net = (float) ($s->net_pay ?? 0);
                    $deduction = 0.0;
                    if (isset($s->cash_loan_period_deduction)) {
                        $deduction = (float) $s->cash_loan_period_deduction;
                    } elseif (isset($s->cash_loan_deduction)) {
                        // alternative column name fallback
                        $deduction = (float) $s->cash_loan_deduction;
                    }
                    return $net - $deduction;
                });

                // 6. Create the main Payroll "group" record
                $payroll = Payroll::create([
                    'period_from'  => $validated['period_from'],
                    'period_to'    => $validated['period_to'],
                    'total_amount' => $totalAmount,
                    'status'       => 'pending',
                ]);

                // record the creating user (set directly so no need to make it fillable)
                if (auth()->check()) {
                    $payroll->created_by = auth()->id();
                    $payroll->save();
                }

                // 7. "Tag" all the found payslips with the new payroll's ID
                //    We use pluck() to get just the IDs,
                //    then do one clean 'update' query.
                $payslipIds = $payslipsToBatch->pluck('id');

                Payslip::whereIn('id', $payslipIds)->update([
                    'payroll_id' => $payroll->id
                ]);

                // 8. Return the new payroll. The transaction will commit.
                return $payroll;
            });

            // If we are here, the transaction was successful
            // don't redirect to a non-existing route; go back with a success flash
            return redirect()->back()
                ->with('success', 'Payroll batch created successfully with ' . $payroll->payslips->count() . ' payslips.');

        } catch (\Exception $e) {
            // If we are here, the transaction failed and rolled back
            return back()->with('error', $e->getMessage());
        }
    }

    // return payslips for a payroll as JSON (used by the modal and CSV generator)
    public function payslips(Payroll $payroll)
    {
        $payslips = Payslip::with('user')
            ->where('payroll_id', $payroll->id)
            ->get()
            ->map(function ($s) {
                $user = $s->user;
                $user_name = null;
                if ($user) {
                    $parts = array_filter([
                        $user->first_name ?? null,
                        $user->middle_name ?? null,
                        $user->last_name ?? null,
                    ]);
                    $user_name = $parts ? implode(' ', $parts) : ($user->email ?? null);
                }

                return [
                    'id' => $s->id,
                    'user_id' => $s->user_id,
                    'user_name' => $user_name,
                    'user' => $user ? [
                        'first_name' => $user->first_name,
                        'middle_name' => $user->middle_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'bank_name' => $user->bank_name,
                        'bank_account_number' => $user->bank_account_number,
                    ] : null,
                    'period_from' => optional($s->period_from)->toDateString(),
                    'period_to' => optional($s->period_to)->toDateString(),
                    'issue_date' => optional($s->issue_date)->toDateString(),
                    'hours_worked' => $s->hours_worked,
                    'hourly_rate' => $s->hourly_rate,
                    'gross_pay' => $s->gross_pay,
                    'adjustments' => $s->adjustments,
                    'net_pay' => $s->net_pay,
                ];
            });

        return response()->json($payslips);
    }

    // update payroll status (expects 'status' in request)
    public function updateStatus(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'status' => 'required|string|max:32',
        ]);
        $newStatus = strtolower($data['status']);
        $payroll->status = $newStatus;
        $payroll->save();

        // If marked as paid, mark all linked payslips as paid.
        if ($newStatus === 'paid') {
            Payslip::where('payroll_id', $payroll->id)->update(['is_paid' => true]);
        } elseif ($newStatus === 'pending') {
            // optionally unmark as paid when moved back to pending
            Payslip::where('payroll_id', $payroll->id)->update(['is_paid' => false]);
        }

        return redirect()->back()->with('success', 'Payroll status updated.');
    }

    // delete payroll (prevent deleting paid payrolls)
    public function destroy(Payroll $payroll)
    {
        // don't allow deletion of already-paid payrolls
        if (strtolower($payroll->status ?? '') === 'paid') {
            return redirect()->back()->with('error', 'Cannot delete a payroll marked as paid.');
        }

        try {
            $payroll->delete();
            return redirect()->back()->with('success', 'Payroll deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete payroll: ' . $e->getMessage());
        }
    }

    /**
     * Stream a CSV download for the payroll's payslips including totals and bank details.
     */
    public function export(Payroll $payroll)
    {
        $fileName = "payroll_{$payroll->id}_payslips.csv";

        $callback = function () use ($payroll) {
            $out = fopen('php://output', 'w');

            // Top metadata rows for readability
            fputcsv($out, ['Payroll ID', $payroll->id]);
            fputcsv($out, ['Period', ($payroll->period_from?->toDateString() ?? '') . ' to ' . ($payroll->period_to?->toDateString() ?? '')]);
            fputcsv($out, ['Generated At', now()->toDateTimeString()]);
            fputcsv($out, []); // blank row

            // Column headers (friendly labels)
            fputcsv($out, [
                'First Name',
                'Middle Name',
                'Last Name',
                'Email',
                'Bank Name',
                'Bank Account',
                'Period From',
                'Period To',
                'Issue Date',
                'Hours Worked',
                'Hourly Rate (CAD)',
                'Gross Pay (CAD)',
                'Adjustments (CAD)',
                'Net Pay (CAD)',
            ]);

            // Totals
            $totalHours = 0.0;
            $totalGross = 0.0;
            $totalAdjustments = 0.0;
            $totalNet = 0.0;

            // Stream payslips in chunks to avoid memory spikes
            Payslip::with('user')
                ->where('payroll_id', $payroll->id)
                ->orderBy('user_id')
                ->chunk(200, function ($chunk) use (&$totalHours, &$totalGross, &$totalAdjustments, &$totalNet, $out) {
                    foreach ($chunk as $s) {
                        $user = $s->user;
                        $first = $user->first_name ?? '';
                        $middle = $user->middle_name ?? '';
                        $last = $user->last_name ?? '';
                        $email = $user->email ?? '';
                        $bankName = $user->bank_name ?? '';
                        $bankAccount = $user->bank_account_number ?? '';

                        $hours = $s->hours_worked ? (float)$s->hours_worked : 0.0;
                        $gross = $s->gross_pay ? (float)$s->gross_pay : 0.0;
                        $adjust = $s->adjustments ? (float)$s->adjustments : 0.0;
                        $net = $s->net_pay ? (float)$s->net_pay : 0.0;

                        $totalHours += $hours;
                        $totalGross += $gross;
                        $totalAdjustments += $adjust;
                        $totalNet += $net;

                        // Write row with formatted numeric values (two decimals)
                        fputcsv($out, [
                            $first,
                            $middle,
                            $last,
                            $email,
                            $bankName,
                            $bankAccount,
                            optional($s->period_from)->toDateString(),
                            optional($s->period_to)->toDateString(),
                            optional($s->issue_date)->toDateString(),
                            number_format($hours, 2),
                            number_format($s->hourly_rate ?? 0, 2),
                            number_format($gross, 2),
                            number_format($adjust, 2),
                            number_format($net, 2),
                        ]);
                    }
                });

            // Blank row then totals row (pretty)
            fputcsv($out, []);
            fputcsv($out, ['TOTALS', '', '', '', '', '', '', '', '', number_format($totalHours, 2), '', number_format($totalGross, 2), number_format($totalAdjustments, 2), number_format($totalNet, 2)]);

            fclose($out);
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        return response()->streamDownload($callback, $fileName, $headers);
    }
}
