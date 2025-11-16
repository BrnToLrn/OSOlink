<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCounter extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type',
        'year',
        'allowance',
        'used',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}