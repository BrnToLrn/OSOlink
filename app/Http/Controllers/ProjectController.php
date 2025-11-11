<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectPermission;
use App\Models\Comment;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    // List projects
    public function index(Request $request)
    {
        $query = Project::query();

        // Only show assigned projects for non-admins
        if (!auth()->user()->is_admin) {
            $query->whereHas('users', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
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
                // This ensures the lead is also in the selected users list
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->has('user_ids') && !in_array($value, $request->user_ids)) {
                        $fail('The project lead must be one of the assigned users.');
                    }
                },
            ],
        ]);

        $validated['created_by'] = auth()->id();

        $project = Project::create($validated);

        // Attach users with their roles
        if ($request->has('user_ids')) {
            $projectLeadId = $request->project_lead_id;

            // Prepare the data for the project_users pivot table
            $usersToAttach = [];
            foreach ($request->user_ids as $userId) {
                // We add the `project_role` to the pivot table data
                $usersToAttach[$userId] = [
                    'project_role' => ($userId == $projectLeadId) ? 'Project Lead' : 'Developer'
                ];
            }

            // Attach all users and their roles in a single database query
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
            $project->users->contains($user->id)
        ) {
            $project->load([
                'users',
                'timeLogs.user',
                'comments.user',
                'comments.replies.user',
                'permissions.user',
                'creator'
            ]);

            // Precompute selected users for AlpineJS
            $selectedUsers = $project->users->map(function($u) {
                return [
                    'id' => $u->id,
                    'first_name' => $u->first_name,
                    'middle_name' => $u->middle_name,
                    'last_name' => $u->last_name,
                    'email' => $u->email,
                    'project_role' => $u->pivot->project_role,
                ];
            })->toArray();

            // Find project lead id
            $projectLeadId = optional($project->users->firstWhere('pivot.project_role', 'Project Lead'))->id;

            // Get all active users not already assigned
            $allUsers = User::where('is_active', true)
                ->whereNotIn('id', $project->users->pluck('id'))
                ->get();

            // Pass everything to the view
            return view('projects.show', compact('project', 'allUsers', 'selectedUsers', 'projectLeadId'));
        }

        abort(403, 'You do not have access to this project.');
    }


    // Edit project
    public function edit(Project $project)
    {
        $users = User::where('is_active', true)->get();
        return view('projects.edit', compact('project', 'users'));
    }

    // Update project
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:Not Started,In Progress,On Hold,Completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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

        $project->update($validated);

        if ($request->has('user_ids')) {
            $projectLeadId = $request->project_lead_id;

            $usersToSync = [];
            foreach ($request->user_ids as $userId) {
                $usersToSync[$userId] = [
                    'project_role' => ($userId == $projectLeadId) ? 'Project Lead' : 'Developer'
                ];
            }
            // `sync` is correct for updates: it adds new users, removes
            // ones that were unchecked, and updates roles for existing ones.
            $project->users()->sync($usersToSync);
        } else {
            // If no users were sent, detach all of them.
            $project->users()->detach();
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Project updated successfully.');
    }

    // Delete project
    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    // Assign/remove users
    public function addUser(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_role' => 'required|string'
        ]);

        // Prevent duplicates
        if ($project->users->contains($request->user_id)) {
            return back()->with('error', 'User already in project.');
        }

        $project->users()->attach($request->user_id, ['project_role' => $request->project_role]);
        return back()->with('success', 'User added successfully.');
    }

    public function updateUserRole(Request $request, Project $project, User $user)
    {
        $request->validate([
            'project_role' => 'required|string'
        ]);

        // Check if user is part of the project
        if (!$project->users->contains($user->id)) {
            return back()->with('error', 'User not part of the project.');
        }

        // Update pivot table
        $project->users()->updateExistingPivot($user->id, ['project_role' => $request->project_role]);
        return back()->with('success', 'User role updated successfully.');
    }

    public function removeUser(Project $project, User $user)
    {
        $project->users()->detach($user->id);
        return back()->with('success', 'User removed.');
    }

    // Permissions
    public function setPermission(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:Viewer,Contributor,Manager',
        ]);

        ProjectPermission::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $validated['user_id']],
            ['role' => $validated['role']]
        );

        return back()->with('success', 'Permission updated.');
    }

    // Comments (allowed even if Completed)
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

    // Add time log
    public function addTimeLog(Request $request, Project $project)
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1',
            'work_output' => 'required|string|max:2000',
            'date' => [
                'required',
                'date',
                'after_or_equal:' . $project->start_date,
                'before_or_equal:' . $project->end_date,
            ],
        ]);

        $project->timeLogs()->create([
            'user_id' => Auth::id(),
            'hours' => $validated['hours'],
            'work_output' => $validated['work_output'],
            'date' => $validated['date'],
        ]);

        return back()->with('success', 'Time log added.');
    }

    // Join project
    public function join(Project $project)
    {
        $user = auth()->user();

        if ($project->users->contains($user->id)) {
            return back()->with('error', 'You are already in this project.');
        }

        if ($project->status === 'Completed') {
            return back()->with('error', 'Cannot join a completed project.');
        }

        $project->users()->attach($user->id);
        return back()->with('success', 'You joined the project.');
    }

    // Leave project
    public function leave(Project $project)
    {
        $user = auth()->user();

        if (!$project->users->contains($user->id)) {
            return back()->with('error', 'You are not part of this project.');
        }

        $project->users()->detach($user->id);
        return back()->with('success', 'You left the project.');
    }

    // Edit time log (show form)
    public function editTimeLog(Project $project, TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$user->is_admin && $timeLog->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        return view('projects.edit-timelog', compact('project', 'timeLog'));
    }

    // Update time log
    public function updateTimeLog(Request $request, Project $project, TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$user->is_admin && $timeLog->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1',
            'work_output' => 'required|string|max:2000',
            'date' => [
                'required',
                'date',
                'after_or_equal:' . $project->start_date,
                'before_or_equal:' . $project->end_date,
            ],
        ]);

        $timeLog->update($validated);

        return redirect()->route('projects.show', [$project->id, 'section' => 'timelogs'])
            ->with('success', 'Time log updated.');
    }

    // Delete time log
    public function deleteTimeLog(Project $project, TimeLog $timeLog)
    {
        $user = auth()->user();
        if (!$user->is_admin && $timeLog->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $timeLog->delete();

        return redirect()->route('projects.show', [$project->id, 'section' => 'timelogs'])
            ->with('success', 'Time log deleted.');
    }
}