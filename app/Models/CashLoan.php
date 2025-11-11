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
    ];

    protected $casts = [
        'date_requested' => 'date',
        'amount'         => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}