<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\CashLoanController;

Route::get('/', fn() => view('auth.login'));

// Dashboard
Route::get('/dashboard', fn() => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    /**
     * =======================
     * PROFILE ROUTES
     * =======================
     */
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
        Route::post('/profile/upload', 'upload')->name('profile.upload');
        Route::delete('/profile/remove', 'remove')->name('profile.remove');
    });

    /**
     * =======================
     * DEPENDENTS
     * =======================
     */
    Route::controller(DependentController::class)->group(function () {
        Route::get('/dependents/create', 'create')->name('create-dependent');
        Route::get('/dependents/{dependent}/edit', 'edit')->name('dependents.edit');
        Route::delete('/dependents/{dependent}', 'destroy')->name('dependents.destroy');
        Route::post('/dependents', 'store')->name('dependents.store');
        Route::get('/dependents', 'index')->name('dependents.index');
        Route::put('/dependents/{dependent}', 'update')->name('dependents.update');
    });

    /**
     * =======================
     * PROJECTS (auth users)
     * =======================
     */
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects/{project}/comments', [ProjectController::class, 'addComment'])->name('projects.comments.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    /**
     * =======================
     * TIMELOGS (nested under projects)
     * =======================
     */
    Route::prefix('projects/{project}')
        ->scopeBindings()
        ->group(function () {
            Route::post('/timelogs', [ProjectController::class, 'addTimeLog'])->name('projects.timelogs.store');
            Route::put('/timelogs/{timelog}', [ProjectController::class, 'updateTimeLog'])->name('projects.timelogs.update');
            Route::delete('/timelogs/{timelog}', [ProjectController::class, 'deleteTimeLog'])->name('projects.timelogs.destroy');
            Route::post('/timelogs/{timelog}/approve', [ProjectController::class, 'approveTimeLog'])->name('projects.timelogs.approve');
            Route::post('/timelogs/{timelog}/decline', [ProjectController::class, 'declineTimeLog'])->name('projects.timelogs.decline');
        });

    /**
     * =======================
     * PROJECTS (admin only)
     * =======================
     */
    Route::middleware('admin')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::post('/projects/{project}/add-user', [ProjectController::class, 'addUser'])->name('projects.addUser');
        Route::put('/projects/{project}/update-user/{user}', [ProjectController::class, 'updateUserRole'])->name('projects.updateUserRole');
        Route::delete('/projects/{project}/remove-user/{user}', [ProjectController::class, 'removeUser'])->name('projects.removeUser');
        Route::post('/projects/{project}/set-permission', [ProjectController::class, 'setPermission'])->name('projects.setPermission');
        Route::put('/projects/{project}/team', [ProjectController::class, 'updateTeam'])->name('projects.updateTeam');
    });

    /**
     * =======================
     * PAYSLIP
     * =======================
     */
    Route::get('/payslip', [PayslipController::class, 'index'])->name('payslip.index');
    Route::get('/payslip/{payslip}', [PayslipController::class, 'show'])->name('payslip.show');

    Route::middleware('admin')->group(function () {
        Route::get('/payslip/manage', [PayslipController::class, 'manage'])->name('payslip.manage');
        Route::post('/payslip', [PayslipController::class, 'store'])->name('payslip.store');
        Route::post('/payslip/calc-hours', [PayslipController::class, 'calculateHours'])->name('payslip.calculateHours');
    });

<<<<<<< HEAD
    /**
     * =======================
     * PAYROLL (admin)
     * =======================
     */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('payrolls/create', [PayrollController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
        Route::post('payrolls/batch', [PayrollController::class, 'batchCreate'])->name('payrolls.batch');
=======
    //Payroll
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('payrolls/create', [\App\Http\Controllers\PayrollController::class, 'create'])->name('payrolls.create');
        Route::post('payrolls/generate', [\App\Http\Controllers\PayrollController::class, 'generate'])->name('payrolls.generate');
        Route::post('payrolls/batch', [\App\Http\Controllers\PayrollController::class, 'batchCreate'])->name('payrolls.batch');

        // Payslips listing for a payroll (JSON for the modal / CSV generator)
        Route::get('payrolls/{payroll}/payslips', [\App\Http\Controllers\PayrollController::class, 'payslips'])->name('payrolls.payslips');

        // Export CSV (server-side stream with totals)
        Route::get('payrolls/{payroll}/export', [\App\Http\Controllers\PayrollController::class, 'export'])->name('payrolls.export');

        // Update status and destroy
        Route::patch('payrolls/{payroll}/status', [\App\Http\Controllers\PayrollController::class, 'updateStatus'])->name('payrolls.updateStatus');
        Route::delete('payrolls/{payroll}', [\App\Http\Controllers\PayrollController::class, 'destroy'])->name('payrolls.destroy');
>>>>>>> f944f14beeaa7d0a1c78fa083f59ec6ec4276df8
    });

    /**
     * =======================
     * LEAVES
     * =======================
     */
    Route::resource('leaves', LeaveController::class)->parameters(['leaves' => 'leave']);

    Route::middleware('admin')->group(function () {
        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('leaves.reject');
        Route::post('/leaves/{leave}/pending', [LeaveController::class, 'pending'])->name('leaves.pending');
    });

    /**
     * =======================
     * CASH LOANS
     * =======================
     */
    Route::resource('cashloans', CashLoanController::class)->parameters(['cashloans' => 'cashloan']);

    Route::middleware('admin')->group(function () {
        Route::post('cashloans/{cashloan}/approve', [CashLoanController::class, 'approve'])->name('cashloans.approve');
        Route::post('cashloans/{cashloan}/reject', [CashLoanController::class, 'reject'])->name('cashloans.reject');
        Route::post('cashloans/{cashloan}/pending', [CashLoanController::class, 'pending'])->name('cashloans.pending');
    });
});

/**
 * =======================
 * ADMIN PANEL
 * =======================
 */
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/adminpanel/admin', [AdminController::class, 'index'])->name('adminpanel.admin');
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle');
    Route::get('/admin/logs', [AdminController::class, 'logs'])->name('admin.logs');
    Route::get('/admin/users/{user}', [AdminController::class, 'show'])->name('admin.users.show');
    Route::patch('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
});

require __DIR__.'/auth.php';
