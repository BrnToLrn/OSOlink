<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectPermission;
use App\Models\Comment;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule; // Import the Rule class

class ProjectController extends Controller
{
    // List projects
    public function index(Request $request)
    {
        $query = Project::query();

        // Only show assigned projects for non-admins
        if (!auth()->user()->is_admin) {
            $query->whereHas('users', fn($q) => $q->where('users.id', auth()->id()));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%'); // DB agnostic
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $projects = $query->with('users')->get();

        return view('projects.index', compact('projects'));
    }

    // Show create form (admin only)
    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('projects.create', compact('users'));
    }

    // Store project
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|in:Not Started,In Progress,On Hold,Completed',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'project_lead_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->has('user_ids') && !in_array($value, $request->user_ids)) {
                        $fail('The project lead must be one of the assigned users.');
                    }
                },
            ],
        ]);

        $validated['created_by'] = auth()->id();
        $project = Project::create($validated);

        // Attach users with roles
        if ($request->has('user_ids')) {
            $projectLeadId = $request->project_lead_id;
            $usersToAttach = [];
            foreach ($request->user_ids as $userId) {
                $usersToAttach[$userId] = [
                    'project_role' => ($userId == $projectLeadId) ? 'Project Lead' : 'Developer'
                ];
            }
            $project->users()->attach($usersToAttach);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project created successfully!');
    }

    // Show project
    public function show(Project $project)
    {
        $user = auth()->user();

        if ($user->is_admin || 
            $project->status === 'Completed' ||
            !$project->users()->exists() ||
            $project->users()->where('users.id', $user->id)->exists()
        ) {
            $project->load([
                'users',
                'timeLogs.user', // Eager load the user for each time log
                'comments.user',
                'comments.replies.user',
                'permissions.user',
                'creator'
            ]);

            $selectedUsers = $project->users->map(fn($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'middle_name' => $u->middle_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
                'project_role' => $u->pivot->project_role,
            ])->toArray();

            $projectLeadId = optional($project->users->firstWhere('pivot.project_role', 'Project Lead'))->id;

            $allUsers = User::where('is_active', true)
                ->get(); // We load all users, Alpine will handle filtering already-added ones
            
            // **NEW LOGIC**
            // 1. Group logs by their formatted date.
            // 2. Map over the *logs* inside each group to include user data.
            $timeLogs = $project->timeLogs->groupBy(function ($log) {
                // Use the Carbon instance from $casts
                return $log->date->format('Y-m-d');
            })->map(function ($logsOnDate) {
                // Map each log in the group to the format Alpine needs
                return $logsOnDate->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'status' => $log->status,
                        'time_in' => $log->time_in,
                        'time_out' => $log->time_out,
                        'work_output' => $log->work_output,
                        'hours' => $log->hours,
                        'decline_reason' => $log->decline_reason,
                        'user_id' => $log->user_id,
                        'user' => $log->user, // Pass the whole user object
                        'user_name' => $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'Unknown User',
                        // We can add this for the edit/delete/approve policy
                        'can_manage' => auth()->user()->is_admin || auth()->id() === $log->user_id
                    ];
                });
            })->toArray(); // Convert the final structure to an array for JSON

            return view('projects.show', compact('project', 'allUsers', 'selectedUsers', 'projectLeadId', 'timeLogs'));
        }

        abort(403, 'You do not have access to this project.');
    }

    // Edit project
    public function edit(Project $project)
    {
        $users = User::where('is_active', true)->get();
        return view('projects.edit', compact('project', 'users'));
    }

    // Update project (Handles both Edit form and Manage Team modal)
    public function update(Request $request, Project $project)
    {
        // 1. Handle "Edit Project" form data
        if ($request->has('name')) {
            $data = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            $project->update($data);
        }

        // 2. Handle "Manage Team" modal data
        if ($request->has('user_ids') || $request->has('project_lead_id')) {
            $userIds = $request->input('user_ids', []);
            $leadId = $request->input('project_lead_id');

            $sync = [];
            foreach ($userIds as $uid) {
                $sync[$uid] = [
                    'project_role' => ($leadId && (string)$uid === (string)$leadId)
                        ? 'Project Lead'
                        : 'Developer',
                ];
            }
            $project->users()->sync($sync);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    // Delete project
    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    // Comments
    public function addComment(Request $request, Project $project)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        $project->comments()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return back()->with('success', 'Comment added.');
    }

    // --- TIME LOGS ---

    // Note: This function is now private and receives $projectId
    private function hasOverlappingTimeLog(Request $request, $projectId, $userId, $exceptLogId = null)
    {
        $start = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_in);
        $end = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time_out);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $query = TimeLog::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('date', $request->date)
            ->when($exceptLogId, fn($q) => $q->where('id', '!=', $exceptLogId))
            // This logic is complex and needs to handle time-only checks.
            // Let's simplify to check for records that start *or* end within the new time.
            ->where(function ($q) use ($start, $end) {
                $startTime = $start->format('H:i:s');
                $endTime = $end->format('H:i:s');

                if ($end->isSameDay($start)) {
                    // Not an overnight log
                    $q->where(function($q2) use ($startTime, $endTime) {
                        $q2->where('time_in', '>=', $startTime)->where('time_in', '<', $endTime);
                    })->orWhere(function($q2) use ($startTime, $endTime) {
                        $q2->where('time_out', '>', $startTime)->where('time_out', '<=', $endTime);
                    })->orWhere(function($q2) use ($startTime, $endTime) {
                        $q2->where('time_in', '<', $startTime)->where('time_out', '>', $endTime);
                    });
                } else {
                    // Is an overnight log, logic is more complex
                    // For now, let's just check for any logs on that day
                    // A proper check would need to query logs on the *next* day as well
                    // This is a simplification to prevent the most obvious overlaps.
                    $q->where('time_in', '>=', '00:00:00') // Just check for *any* log
                      ->orWhere('time_out', '<=', '23:59:59');
                }
            });


        if ($query->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => ['time_in' => ['There is a conflict with an existing time log for this day. Please check your entries.']]
                ], 422);
            }

            return back()
                ->withErrors(['time_in' => 'There is a conflict with an existing time log for this day. Please check your entries.'])
                ->with('section', 'timelogs');
        }

        return false; // No overlap
    }

    // **CHANGED**: Now only takes Request
    public function addTimeLog(Project $project, Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'work_output' => 'required|string|max:2000',
        ]);
        
        // Check project date boundaries
        if ($validated['date'] < $project->start_date || $validated['date'] > $project->end_date) {
             return response()->json([
                'errors' => ['date' => ['The date must be within the project start and end dates.']]
            ], 422);
        }

        $overlapResponse = $this->hasOverlappingTimeLog($request, $project->id, auth()->id());
        if ($overlapResponse) {
            return $overlapResponse; // Returns JSON or back()
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time_in']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time_out']);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // handles overnight work (e.g., 11pm–2am)
        }

        $minutes = $start->diffInMinutes($end);
        $hours = round($minutes / 60, 2);
        
        $role = $project->users()->where('user_id', auth()->id())->first()->pivot->project_role ?? 'Developer';

        $status = $role === 'Project Lead' ? 'Approved' : 'Pending';

        $timeLog = $project->timeLogs()->create([
            'user_id' => Auth::id(),
            'date' => $validated['date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'hours' => $hours,
            'work_output' => $validated['work_output'],
            'status' => $status,
            'decline_reason' => null,
        ]);
        
        // Eager load the user for the response
        $timeLog->load('user');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Time log created successfully',
                'log' => $timeLog // Return the new log
            ]);
        }

        return back()->with('success', 'Time log created successfully');
    }

    // **CHANGED**: Now takes TimeLog instead of Project
    public function updateTimeLog(Request $request, Project $project, TimeLog $timelog)
    {
        // Check the timelog belongs to this project
        if ($timelog->project_id !== $project->id) {
            abort(404); // or return JSON error
        }

        $user = auth()->user();
        if (!$user->is_admin && $timelog->user_id !== $user->id) abort(403);

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:' . $project->start_date, 'before_or_equal:' . $project->end_date],
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'work_output' => 'required|string|max:2000',
        ]);

        $overlapResponse = $this->hasOverlappingTimeLog($request, $project->id, $timelog->user_id, $timelog->id);
        if ($overlapResponse) {
            return $overlapResponse;
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time_in']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $validated['date'] . ' ' . $validated['time_out']);
        if ($end->lessThanOrEqualTo($start)) $end->addDay();

        $hours = round($start->diffInMinutes($end) / 60, 2);

        $timelog->update([
            'date' => $validated['date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'hours' => $hours,
            'work_output' => $validated['work_output'],
            'status' => 'Pending',
            'decline_reason' => null,
        ]);

        $timelog->load('user');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Time log updated successfully',
                'log' => $timelog
            ]);
        }

        return redirect()
            ->route('projects.show', ['project' => $project->id, 'section' => 'timelogs'])
            ->with('success', 'Time log updated and resubmitted for approval.');
    }

    public function deleteTimeLog(Project $project, TimeLog $timelog)
    {
        // Ensure the timelog belongs to this project
        abort_if($timelog->project_id !== $project->id, 404);

        $user = auth()->user();

        // Only the owner of the timelog or an admin can delete it
        if (!$user->is_admin && $timelog->user_id !== $user->id) {
            abort(403, 'You do not have permission to delete this time log.');
        }

        $timelog->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Time log deleted successfully',
                'log_id' => $timelog->id,
            ]);
        }

        return back()->with('success', 'Time log deleted successfully.');
    }

    // **CHANGED**: Now takes TimeLog instead of Project
    public function approveTimeLog(Project $project, TimeLog $timelog)
    {
        // Optional: ensure the timelog belongs to this project
        abort_if($timelog->project_id !== $project->id, 404);

        $user = auth()->user();
        $leadId = optional($project->users->firstWhere('pivot.project_role', 'Project Lead'))->id;

        if (!$user->is_admin && $user->id !== $leadId) {
            abort(403, 'Only the project lead can approve time logs.');
        }

        $timelog->update([
            'status' => 'Approved',
            'decline_reason' => null
        ]);

        return back()->with('success', 'Time log approved.');
    }


    // **CHANGED**: Now takes TimeLog instead of Project
    public function declineTimeLog(Request $request, Project $project, TimeLog $timelog)
    {
        $user = auth()->user();
        $project = $timelog->project;
        $leadId = optional($project->users->firstWhere('pivot.project_role', 'Project Lead'))->id;

        if (!$user->is_admin && $user->id !== $leadId) {
            abort(403, 'Only the project lead can decline time logs.');
        }

        $validated = $request->validate([
            'decline_reason' => 'required|string|max:500'
        ]);

        $timelog->update([
            'status' => 'Declined',
            'decline_reason' => $validated['decline_reason']
        ]);

        return back()->with('success', 'Time log declined.');
    }


    // Join / Leave project
    public function join(Project $project)
    {
        $user = auth()->user();

        if ($project->users()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'You are already in this project.');
        }

        if ($project->status === 'Completed') {
            return back()->with('error', 'Cannot join a completed project.');
        }

        $project->users()->attach($user->id, ['project_role' => 'Developer']); // Default role
        return back()->with('success', 'You joined the project.');
    }

    public function leave(Project $project)
    {
        $user = auth()->user();

        if (!$project->users()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'You are not part of this project.');
        }

        $project->users()->detach($user->id);
        return back()->with('success', 'You left the project.');
    }
}