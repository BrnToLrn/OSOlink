<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class HourlyRateHistory extends Model
{
    protected $table = 'hourly_rate_history';

    // if your table uses created_at/updated_at keep timestamps = true, otherwise set false
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'changed_by',
        'old_rate',
        'new_rate',
    ];

    // relation to the user who made the change
    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
