<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\LeaveController;

Route::get('/', function () {
    return view('auth.login');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/upload', [ProfileController::class, 'upload'])->name('profile.upload');
    Route::delete('/profile/remove', [ProfileController::class, 'remove'])->name('profile.remove');

    // Dependents
    Route::get('/dependents/create', [DependentController::class, 'create'])->name('create-dependent');
    Route::get('/dependents/{dependent}/edit', [DependentController::class, 'edit'])->name('dependents.edit');
    Route::delete('/dependents/{dependent}', [DependentController::class, 'destroy'])->name('dependents.destroy');
    Route::post('/dependents', [DependentController::class, 'store'])->name('dependents.store');
    Route::get('/dependents', [DependentController::class, 'index'])->name('dependents.index');
    Route::put('/dependents/{dependent}', [DependentController::class, 'update'])->name('dependents.update');

    // Projects (admin-only, static routes FIRST)
    Route::middleware('admin')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    });

    // Projects (public to authenticated)
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects/{project}/comments', [ProjectController::class, 'addComment'])->name('projects.comments.store');
    Route::post('/projects/{project}/timelogs', [ProjectController::class, 'addTimeLog'])->name('projects.addTimeLog');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/timelogs/{timeLog}/edit', [ProjectController::class, 'editTimeLog'])->name('projects.editTimeLog');
    Route::put('/projects/{project}/timelogs/{timeLog}', [ProjectController::class, 'updateTimeLog'])->name('projects.updateTimeLog');
    Route::delete('/projects/{project}/timelogs/{timeLog}', [ProjectController::class, 'deleteTimeLog'])->name('projects.deleteTimeLog');
    Route::post('/projects/{project}/timelogs/{timeLog}/approve', [ProjectController::class, 'approveTimeLog'])->name('projects.approveTimeLog');
    Route::post('/projects/{project}/timelogs/{timeLog}/decline', [ProjectController::class, 'declineTimeLog'])->name('projects.declineTimeLog');

    // Projects (admin-only)
    Route::middleware('admin')->group(function () {
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/add-user', [ProjectController::class, 'addUser'])->name('projects.addUser');
        Route::put('/projects/{project}/update-user/{user}', [ProjectController::class, 'updateUserRole'])->name('projects.updateUserRole');
        Route::delete('/projects/{project}/remove-user/{user}', [ProjectController::class, 'removeUser'])->name('projects.removeUser');
        Route::post('/projects/{project}/set-permission', [ProjectController::class, 'setPermission'])->name('projects.setPermission');
        Route::put('/projects/{project}/team', [ProjectController::class, 'updateTeam'])->name('projects.updateTeam');
    });

    // Payroll (Records first)
    Route::get('/payroll', [PayrollController::class, 'records'])->name('payroll.index'); // default: records
    Route::get('/payroll/create', [PayrollController::class, 'index'])->name('payroll.create'); // add form
    // Back-compat: old /payroll/records -> redirect to index
    Route::get('/payroll/records', function () {
        return redirect()->route('payroll.index');
    })->name('payroll.records');
    // Hours endpoint
    Route::get('/payroll/hours', [PayrollController::class, 'hours'])->name('payroll.hours');

    Route::middleware('admin')->group(function () {
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payroll}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::put('/payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    });

    // Payslip
    Route::get('/payslip', [PayslipController::class, 'index'])->name('payslip.index');
    Route::get('/payslip/{payslip}', [PayslipController::class, 'show'])->name('payslip.show');
    Route::post('/payslip', [PayslipController::class, 'store'])->name('payslip.store');

    // Leaves — shared by admin and employees
    Route::resource('leaves', LeaveController::class)->parameters(['leaves' => 'leave']);
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
    Route::post('/leaves/{leave}/pending', [LeaveController::class, 'pending'])->name('leaves.pending');
});

// Admin panel routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/adminpanel/admin', [AdminController::class, 'index'])->name('adminpanel.admin');
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::get('/admin/logs', [AdminController::class, 'logs'])->name('admin.logs');
    Route::get('/admin/users/{user}', [AdminController::class, 'show'])->name('admin.users.show');
    Route::patch('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
});

require __DIR__.'/auth.php';