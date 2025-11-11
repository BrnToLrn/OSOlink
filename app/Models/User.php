<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'job_type',
        'hourly_rate',
        'gender',
        'phone',
        'birthday',
        'country',
        'state',
        'zip',
        'address',
        'bank_name',
        'bank_account_number',
        'profile_picture',
        'password',
        'is_admin', 
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')->withTimestamps();
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function projectPermissions()
    {
        return $this->hasMany(ProjectPermission::class);
    }

    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    public function leaves()
    {
        return $this->hasMany(\App\Models\Leave::class);
    }
    
    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }
}