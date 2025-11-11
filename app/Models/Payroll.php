<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = [
        'period_from',
        'period_to',
        'pay_date',
        'total_amount',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
        'pay_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // If you use payslips as fallback for period dates
    public function payslip()
    {
        return $this->hasOne(\App\Models\Payslip::class, 'payroll_id');
    }
}