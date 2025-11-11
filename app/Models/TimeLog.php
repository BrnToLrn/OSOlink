<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        'approved_by',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the time log.
     */
    public function approve()
    {
        $this->update([
            'status' => 'Approved',
            'decline_reason' => null,
            'approved_by' => Auth::id(), // Record who approved it
        ]);
    }

    /**
     * Decline the time log.
     *
     * @param string $reason
     */
    public function decline($reason)
    {
        $this->update([
            'status' => 'Declined',
            'decline_reason' => $reason,
            'approved_by' => null, // Clear approver if it was previously approved
        ]);
    }
}