<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payroll_id',
        'period_from',
        'period_to',
        'issue_date',
        'hours_worked',
        'hourly_rate',
        'gross_pay',
        'adjustments',
        'cash_loan_id',
        'cash_loan_period_number',
        'cash_loan_period_deduction',
        'net_pay',
        'is_paid',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
        'issue_date'  => 'date',
        'hours_worked' => 'decimal:2',
        'hourly_rate'  => 'decimal:2',
        'gross_pay'    => 'decimal:2',
        'adjustments'  => 'decimal:2',
        'cash_loan_period_deduction' => 'decimal:2',
        'net_pay'      => 'decimal:2',
        'is_paid'      => 'boolean',
    ];

    protected $attributes = [
        'is_paid' => false,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function cashLoan()
    {
        return $this->belongsTo(\App\Models\CashLoan::class, 'cash_loan_id');
    }

    public function adjustmentItems()
    {
        return $this->hasMany(\App\Models\Adjustment::class);
    }

    public function setLoanInstallment(\App\Models\CashLoan $loan, int $periodNumber): self
    {
        $periods = max(1, (int)$loan->pay_periods);
        $this->cash_loan_id = $loan->id;
        $this->cash_loan_period_number = max(1, min($periodNumber, $periods));
        $this->cash_loan_period_deduction = round((float)$loan->amount / $periods, 2);
        return $this;
    }

    public function recomputeTotals(): self
    {
        if (is_null($this->gross_pay) && isset($this->hours_worked, $this->hourly_rate)) {
            $this->gross_pay = round((float)$this->hours_worked * (float)$this->hourly_rate, 2);
        }
        $net = (float)($this->gross_pay ?? 0)
             + (float)($this->adjustments ?? 0)
             - (float)($this->cash_loan_period_deduction ?? 0);
        $this->net_pay = round($net, 2);
        return $this;
    }

    public function scopePaid($q)
    {
        return $q->where('is_paid', true);
    }

    public function scopeUnpaid($q)
    {
        return $q->where('is_paid', false);
    }

    public function scopeForLoanPeriod($q, int $loanId, int $period)
    {
        return $q->where('cash_loan_id', $loanId)->where('cash_loan_period_number', $period);
    }

    // public function adjustments()
    // {
        //return $this->hasMany(\App\Models\Adjustment::class);
    // }

}