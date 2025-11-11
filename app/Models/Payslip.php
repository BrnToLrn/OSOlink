<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_from',
        'period_to',
        'hours_worked',
        'hourly_rate',
        'gross_pay',
        'adjustments',
        'net_pay',
        'issue_date',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
        'issue_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function adjustments()
    {
        return $this->hasMany(\App\Models\Adjustment::class);
    }
}