<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'work_output',
        'date',
        'time_in',
        'time_out',
        'hours',
        'status',
        'decline_reason',
    ];

    protected $casts = [
        'hours' => 'float',
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public function approve()
    {
        $this->update(['status' => 'Approved', 'decline_reason' => null]);
    }

    public function decline($reason)
    {
        $this->update(['status' => 'Declined', 'decline_reason' => $reason]);
    }
}