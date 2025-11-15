<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Make sure this is imported

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'assigned_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * THIS IS THE FIX for the 'format() on string' error
     * This tells Laravel to automatically convert these
     * database columns into Carbon date objects.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')
                        ->withPivot('project_role')
                        ->withTimestamps();
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function permissions()
    {
        return $this->hasMany(ProjectPermission::class);
    }

    /**
     * THIS IS THE FIX for the 'creator' relationship error
     * Get the user who created the project.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * This is the other relationship you were missing.
     * Get the user this project was assigned to (if any).
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}