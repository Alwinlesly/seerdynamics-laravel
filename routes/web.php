<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimesheetApprovalController;
use App\Http\Controllers\UserProfileController;

// Authentication Routes
Route::get('/', [LoginController::class, 'index'])->name('login');
Route::get('/auth', [LoginController::class, 'index'])->name('login.form');
Route::get('/auth/login', [LoginController::class, 'index']);
Route::post('/auth/login', [LoginController::class, 'login'])->name('login.submit');
Route::get('/auth/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::get('/auth/reset-password/{code}', [ResetPasswordController::class, 'showResetForm'])->where('code', '.*');
Route::post('/auth/reset-password', [ResetPasswordController::class, 'reset']);

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/users/profile', [UserProfileController::class, 'show'])->name('users.profile');
    Route::post('/users/profile', [UserProfileController::class, 'update'])->name('users.profile.update');
    
    // Projects Routes
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/list', [ProjectController::class, 'getProjects'])->name('projects.list');
        Route::get('/timesheetapprovals', [TimesheetApprovalController::class, 'index'])->name('timesheets.approvals');
        Route::get('/timesheetapprovals/list', [TimesheetApprovalController::class, 'list'])->name('timesheets.approvals.list');
        Route::post('/timesheetapprovals/details', [TimesheetApprovalController::class, 'details'])->name('timesheets.approvals.details');
        Route::post('/timesheetapprovals/{id}/approve', [TimesheetApprovalController::class, 'approve'])->name('timesheets.approvals.approve');
        Route::post('/timesheetapprovals/{id}/reject', [TimesheetApprovalController::class, 'reject'])->name('timesheets.approvals.reject');
        Route::get('/{id}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::get('/{id}', [ProjectController::class, 'show'])->name('projects.show');
        Route::post('/store', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/{id}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        
        // File management
        Route::post('/{id}/upload-file', [ProjectController::class, 'uploadFile'])->name('projects.uploadFile');
        Route::delete('/{id}/files/{fileId}', [ProjectController::class, 'deleteFile'])->name('projects.deleteFile');
    });
    
    // Tasks Routes
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/list', [TaskController::class, 'getTasks'])->name('tasks.list');
        Route::get('/export', [TaskController::class, 'export'])->name('tasks.export');
        Route::get('/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::get('/{id}', [TaskController::class, 'show'])->name('tasks.show');
        Route::post('/store', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/{id}/close', [TaskController::class, 'close'])->name('tasks.close');
        Route::put('/{id}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        
        // Timer routes
        Route::post('/{id}/timer/start', [TaskController::class, 'startTimer'])->name('tasks.timer.start');
        Route::post('/{id}/timer/stop', [TaskController::class, 'stopTimer'])->name('tasks.timer.stop');
        Route::get('/{id}/timer/status', [TaskController::class, 'timerStatus'])->name('tasks.timer.status');
        
        // Comment routes
        Route::post('/{id}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');

        // Estimate routes
        Route::get('/{id}/estimates', [TaskController::class, 'getEstimates'])->name('tasks.estimates.list');
        Route::post('/{id}/estimates', [TaskController::class, 'storeEstimate'])->name('tasks.estimates.store');
        Route::post('/estimates/{estimateId}/approve', [TaskController::class, 'approveEstimate'])->name('tasks.estimates.approve');
    });
    
    // Customers Routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
        Route::post('/list', [App\Http\Controllers\CustomerController::class, 'getCustomers'])->name('customers.list');
        Route::get('/export', [App\Http\Controllers\CustomerController::class, 'export'])->name('customers.export');
        Route::get('/{id}/edit', [App\Http\Controllers\CustomerController::class, 'edit'])->name('customers.edit');
        Route::get('/{id}', [App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
        Route::post('/store', [App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
        Route::put('/{id}', [App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/{id}', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
    });
    
    // Customer Users Routes
    Route::prefix('users/client')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomerUserController::class, 'index'])->name('customer-users.index');
        Route::post('/list', [App\Http\Controllers\CustomerUserController::class, 'getCustomerUsers'])->name('customer-users.list');
        Route::post('/store', [App\Http\Controllers\CustomerUserController::class, 'store'])->name('customer-users.store');
        Route::get('/{id}/edit', [App\Http\Controllers\CustomerUserController::class, 'edit'])->name('customer-users.edit');
        Route::put('/{id}', [App\Http\Controllers\CustomerUserController::class, 'update'])->name('customer-users.update');
        Route::delete('/{id}', [App\Http\Controllers\CustomerUserController::class, 'destroy'])->name('customer-users.destroy');
    });
    
    // Timesheet Routes
    Route::prefix('timesheet')->group(function () {
        Route::get('/', [App\Http\Controllers\TimesheetController::class, 'index'])->name('timesheets.index');
        Route::get('/get', [App\Http\Controllers\TimesheetController::class, 'getTimesheets'])->name('timesheets.get');
        Route::get('/create', [App\Http\Controllers\TimesheetController::class, 'create'])->name('timesheets.create');
        Route::post('/store', [App\Http\Controllers\TimesheetController::class, 'store'])->name('timesheets.store');
        Route::delete('/{id}', [App\Http\Controllers\TimesheetController::class, 'destroy'])->name('timesheets.destroy');
        Route::get('/{id}/show', [App\Http\Controllers\TimesheetController::class, 'show'])->name('timesheets.show');
        Route::get('/{id}/edit', [App\Http\Controllers\TimesheetController::class, 'edit'])->name('timesheets.edit');
        Route::post('/{id}/update', [App\Http\Controllers\TimesheetController::class, 'update'])->name('timesheets.update');

        // AJAX helper endpoints for create/edit form
        Route::get('/customers', [App\Http\Controllers\TimesheetController::class, 'getCustomers'])->name('timesheets.customers');
        Route::post('/projects-by-customer', [App\Http\Controllers\TimesheetController::class, 'getProjectsByCustomer'])->name('timesheets.projects-by-customer');
        Route::post('/tasks-by-project', [App\Http\Controllers\TimesheetController::class, 'getTasksByProject'])->name('timesheets.tasks-by-project');
        Route::post('/day-totals', [App\Http\Controllers\TimesheetController::class, 'getDayTotalHours'])->name('timesheets.day-totals');
        
        // Timesheet Release Routes
        Route::get('/release', [App\Http\Controllers\TimesheetReleaseController::class, 'index'])->name('timesheets.release');
        Route::get('/release/data', [App\Http\Controllers\TimesheetReleaseController::class, 'getData'])->name('timesheets.release.data');
        Route::post('/release/save', [App\Http\Controllers\TimesheetReleaseController::class, 'saveRelease'])->name('timesheets.release.save');
        Route::post('/release/projects', [App\Http\Controllers\TimesheetReleaseController::class, 'getProjectsByCustomer'])->name('timesheets.release.projects');
    });
    
    // Support Statement Routes
    Route::prefix('support-statement')->group(function () {
        Route::get('/', [App\Http\Controllers\SupportStatementController::class, 'index'])->name('support-statement.index');
        Route::post('/report', [App\Http\Controllers\SupportStatementController::class, 'reportView'])->name('support-statement.report');
        Route::post('/print', [App\Http\Controllers\SupportStatementController::class, 'generatePrint'])->name('support-statement.print');
        Route::post('/projects', [App\Http\Controllers\SupportStatementController::class, 'getProjectsByCustomer'])->name('support-statement.projects');
    });
    
    // Consultants Routes
    Route::prefix('consultants')->group(function () {
        Route::get('/', [App\Http\Controllers\ConsultantController::class, 'index'])->name('consultants.index');
        Route::post('/store', [App\Http\Controllers\ConsultantController::class, 'store'])->name('consultants.store');
        Route::get('/{id}', [App\Http\Controllers\ConsultantController::class, 'getById'])->name('consultants.get');
        Route::post('/update', [App\Http\Controllers\ConsultantController::class, 'update'])->name('consultants.update');
        Route::delete('/{id}', [App\Http\Controllers\ConsultantController::class, 'destroy'])->name('consultants.destroy');
        Route::post('/activate/{id}', [App\Http\Controllers\ConsultantController::class, 'activate'])->name('consultants.activate');
        Route::post('/deactivate/{id}', [App\Http\Controllers\ConsultantController::class, 'deactivate'])->name('consultants.deactivate');
    });
});

// Admin Only Routes (example)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin routes here
});

// Custom User Admin Routes (example)
Route::middleware(['auth', 'role:cuser admin'])->group(function () {
    // Custom user admin routes here
});
