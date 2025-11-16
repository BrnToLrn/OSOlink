<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashLoan extends Model
{
    protected $fillable = [
        'user_id',
        'date_requested',
        'amount',
        'type',
        'status',
        'remarks',
        'pay_periods', // 1..6
    ];

    protected $casts = [
        'date_requested' => 'date',
        'amount'         => 'decimal:2',
        'pay_periods'    => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}