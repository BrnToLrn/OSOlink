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
        'created_by', // allow mass assignment
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to'   => 'date',
    ];

    // Use the correct FK for creator
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Optional explicit alias
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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