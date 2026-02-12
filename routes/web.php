<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;

// Authentication Routes
Route::get('/', [LoginController::class, 'index'])->name('login.form');
Route::get('/auth', [LoginController::class, 'index']);
Route::post('/auth/login', [LoginController::class, 'login'])->name('login');
Route::get('/auth/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::get('/auth/reset-password/{code}', [ResetPasswordController::class, 'showResetForm']);
Route::post('/auth/reset-password', [ResetPasswordController::class, 'reset']);

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Projects Routes
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('/list', [ProjectController::class, 'getProjects'])->name('projects.list');
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
        Route::put('/{id}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        
        // Timer routes
        Route::post('/{id}/timer/start', [TaskController::class, 'startTimer'])->name('tasks.timer.start');
        Route::post('/{id}/timer/stop', [TaskController::class, 'stopTimer'])->name('tasks.timer.stop');
        Route::get('/{id}/timer/status', [TaskController::class, 'timerStatus'])->name('tasks.timer.status');
        
        // Comment routes
        Route::post('/{id}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');
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