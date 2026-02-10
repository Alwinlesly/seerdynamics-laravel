<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Projects Routes
Route::middleware(['auth'])->prefix('projects')->group(function () {
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
