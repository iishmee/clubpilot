<?php

// Routes for Volunteer Management (F11-F15).
// This file is loaded from routes/web.php inside the "auth" middleware group.

use App\Http\Controllers\ShiftController;
use App\Http\Controllers\VolunteerTaskController;
use Illuminate\Support\Facades\Route;

// F12: shift scheduling
Route::get('/volunteer-opportunities/{opportunity}/shifts', [ShiftController::class, 'index'])->name('shifts.index');
Route::post('/volunteer-opportunities/{opportunity}/shifts', [ShiftController::class, 'store'])->name('shifts.store');
Route::post('/shifts/{shift}/assign', [ShiftController::class, 'assign'])->name('shifts.assign');
Route::delete('/shift-assignments/{assignment}', [ShiftController::class, 'unassign'])->name('shifts.unassign');
Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
Route::get('/my-shifts', [ShiftController::class, 'mine'])->name('shifts.mine');

// F13: volunteer task management
Route::get('/volunteer-opportunities/{opportunity}/tasks', [VolunteerTaskController::class, 'index'])->name('tasks.index');
Route::post('/volunteer-opportunities/{opportunity}/tasks', [VolunteerTaskController::class, 'store'])->name('tasks.store');
Route::post('/tasks/{task}/assign', [VolunteerTaskController::class, 'assign'])->name('tasks.assign');
Route::post('/tasks/{task}/status', [VolunteerTaskController::class, 'updateStatus'])->name('tasks.status');
Route::delete('/tasks/{task}', [VolunteerTaskController::class, 'destroy'])->name('tasks.destroy');
Route::get('/my-tasks', [VolunteerTaskController::class, 'mine'])->name('tasks.mine');
