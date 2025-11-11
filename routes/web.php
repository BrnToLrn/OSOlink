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
    Route::get('/projects/{project}/timelogs/{timeLog}/edit', [ProjectController::class, 'editTimeLog'])->scopeBindings()->name('projects.editTimeLog');
    Route::put('/projects/{project}/timelogs/{timeLog}', [ProjectController::class, 'updateTimeLog'])->scopeBindings()->name('projects.updateTimeLog');
    Route::delete('/projects/{project}/timelogs/{timeLog}', [ProjectController::class, 'deleteTimeLog'])->scopeBindings()->name('projects.deleteTimeLog');
    Route::put('/projects/{project}/timelogs/{timeLog}/approve', [ProjectController::class, 'approveTimeLog'])->scopeBindings()->name('projects.approveTimeLog');
    Route::put('/projects/{project}/timelogs/{timeLog}/decline', [ProjectController::class, 'declineTimeLog'])->scopeBindings()->name('projects.declineTimeLog');


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

    // Payslip
    Route::get('/payslip', [PayslipController::class, 'index'])->name('payslip.index');
    Route::get('/payslip/{payslip}', [PayslipController::class, 'show'])->name('payslip.show');
    // Payslip Admin actions
    Route::middleware('admin')->group(function () {
        Route::get('/payslip/manage', [PayslipController::class, 'manage'])->name('payslip.manage');
        Route::post('/payslip', [PayslipController::class, 'store'])->name('payslip.store');
        Route::post('/payslip/calc-hours', [PayslipController::class, 'calculateHours'])->name('payslip.calculateHours');
    });

    //Payroll
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('payrolls/create', [\App\Http\Controllers\PayrollController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls/generate', [\App\Http\Controllers\PayrollController::class, 'generate'])->name('payrolls.generate');
    });

    // Leaves
    Route::resource('leaves', LeaveController::class)->parameters(['leaves' => 'leave']);
    // Leaves Admin actions
    Route::middleware('admin')->group(function () {
        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::post('/leaves/{leave}/pending', [LeaveController::class, 'pending'])->name('leaves.pending');
    });
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