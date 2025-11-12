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

class PayrollController extends Controller
{
    public function batchCreate(Request $request)
    {
        // 1. Validate the form data from the modal
        $validated = $request->validate([
            'period_from' => 'required|date',
            'period_to'   => 'required|date|after_or_equal:period_from',
        ]);

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
                //    *** I am assuming your 'payslips' table has
                //    a 'net_pay' column. Change this as needed.
                $totalAmount = $payslipsToBatch->sum('net_pay');

                // 6. Create the main Payroll "group" record
                $payroll = Payroll::create([
                    'period_from'  => $validated['period_from'],
                    'period_to'    => $validated['period_to'],
                    'total_amount' => $totalAmount,
                    'status'       => 'Pending',
                ]);

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
}
