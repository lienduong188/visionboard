<?php

use App\Http\Controllers\GoalController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Redirect home to Vision Board
Route::get('/', function () {
    return redirect()->route('goals.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard redirects to goals
    Route::get('/dashboard', function () {
        return redirect()->route('goals.index');
    })->name('dashboard');

    // Goals (Vision Board)
    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('/goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::patch('/goals/{goal}/progress', [GoalController::class, 'updateProgress'])->name('goals.progress');
    Route::patch('/goals/{goal}/pin', [GoalController::class, 'togglePin'])->name('goals.pin');
    Route::patch('/goals/{goal}/orbit-scale', [GoalController::class, 'updateOrbitScale'])->name('goals.orbit-scale');
    Route::post('/goals/reorder', [GoalController::class, 'reorder'])->name('goals.reorder');

    // Milestones
    Route::post('/goals/{goal}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::put('/goals/{goal}/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::patch('/goals/{goal}/milestones/{milestone}/toggle', [MilestoneController::class, 'toggle'])->name('milestones.toggle');
    Route::post('/goals/{goal}/milestones/reorder', [MilestoneController::class, 'reorder'])->name('milestones.reorder');
    Route::delete('/goals/{goal}/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
