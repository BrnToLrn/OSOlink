<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = [
        'period_from',
        'period_to',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
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

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}