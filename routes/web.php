<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\GoalChecklistController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\MilestoneTodoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressLogController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReviewSettingController;
use App\Http\Controllers\GoalReferenceController;
use App\Http\Controllers\ThemeWordController;
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
    Route::get('/goals/export/csv', [GoalController::class, 'exportCsv'])->name('goals.export.csv');
    Route::get('/goals/export/pdf', [GoalController::class, 'exportPdf'])->name('goals.export.pdf');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/reorder', [GoalController::class, 'reorder'])->name('goals.reorder');
    Route::get('/goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::patch('/goals/{goal}/progress', [GoalController::class, 'updateProgress'])->name('goals.progress');
    Route::patch('/goals/{goal}/pin', [GoalController::class, 'togglePin'])->name('goals.pin');
    Route::patch('/goals/{goal}/orbit-scale', [GoalController::class, 'updateOrbitScale'])->name('goals.orbit-scale');

    // Milestones
    Route::post('/goals/{goal}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::put('/goals/{goal}/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::patch('/goals/{goal}/milestones/{milestone}/toggle', [MilestoneController::class, 'toggle'])->name('milestones.toggle');
    Route::patch('/goals/{goal}/milestones/{milestone}/toggle-soft', [MilestoneController::class, 'toggleSoft'])->name('milestones.toggle-soft');
    Route::post('/goals/{goal}/milestones/reorder', [MilestoneController::class, 'reorder'])->name('milestones.reorder');
    Route::delete('/goals/{goal}/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');

    // Milestone Todos
    Route::post('/goals/{goal}/milestones/{milestone}/todos', [MilestoneTodoController::class, 'store'])->name('milestone-todos.store');
    Route::put('/goals/{goal}/milestones/{milestone}/todos/{todo}', [MilestoneTodoController::class, 'update'])->name('milestone-todos.update');
    Route::patch('/goals/{goal}/milestones/{milestone}/todos/{todo}/toggle', [MilestoneTodoController::class, 'toggle'])->name('milestone-todos.toggle');
    Route::post('/goals/{goal}/milestones/{milestone}/todos/reorder', [MilestoneTodoController::class, 'reorder'])->name('milestone-todos.reorder');
    Route::delete('/goals/{goal}/milestones/{milestone}/todos/{todo}', [MilestoneTodoController::class, 'destroy'])->name('milestone-todos.destroy');

    // Goal Checklists
    Route::post('/goals/{goal}/checklists', [GoalChecklistController::class, 'store'])->name('checklists.store');
    Route::put('/goals/{goal}/checklists/{checklist}', [GoalChecklistController::class, 'update'])->name('checklists.update');
    Route::patch('/goals/{goal}/checklists/{checklist}/toggle', [GoalChecklistController::class, 'toggle'])->name('checklists.toggle');
    Route::post('/goals/{goal}/checklists/reorder', [GoalChecklistController::class, 'reorder'])->name('checklists.reorder');
    Route::delete('/goals/{goal}/checklists/{checklist}', [GoalChecklistController::class, 'destroy'])->name('checklists.destroy');

    // Reminders
    Route::post('/goals/{goal}/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::put('/goals/{goal}/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::patch('/goals/{goal}/reminders/{reminder}/toggle', [ReminderController::class, 'toggle'])->name('reminders.toggle');
    Route::delete('/goals/{goal}/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

    // Progress Logs
    Route::post('/goals/{goal}/progress-logs', [ProgressLogController::class, 'store'])->name('progress-logs.store');
    Route::put('/goals/{goal}/progress-logs/{progressLog}', [ProgressLogController::class, 'update'])->name('progress-logs.update');
    Route::delete('/goals/{goal}/progress-logs/{progressLog}', [ProgressLogController::class, 'destroy'])->name('progress-logs.destroy');

    // Goal References
    Route::post('/goals/{goal}/references', [GoalReferenceController::class, 'store'])->name('references.store');
    Route::put('/goals/{goal}/references/{reference}', [GoalReferenceController::class, 'update'])->name('references.update');
    Route::post('/goals/{goal}/references/reorder', [GoalReferenceController::class, 'reorder'])->name('references.reorder');
    Route::delete('/goals/{goal}/references/{reference}', [GoalReferenceController::class, 'destroy'])->name('references.destroy');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Review Settings
    Route::get('/settings/reviews', [ReviewSettingController::class, 'show'])->name('settings.reviews');
    Route::put('/settings/reviews', [ReviewSettingController::class, 'update'])->name('settings.reviews.update');

    // Theme Words
    Route::post('/theme-words', [ThemeWordController::class, 'store'])->name('theme-words.store');
    Route::put('/theme-words/{themeWord}', [ThemeWordController::class, 'update'])->name('theme-words.update');
    Route::delete('/theme-words/{themeWord}', [ThemeWordController::class, 'destroy'])->name('theme-words.destroy');
    Route::post('/theme-words/reorder', [ThemeWordController::class, 'reorder'])->name('theme-words.reorder');
    Route::patch('/theme-words/effect', [ThemeWordController::class, 'updateEffect'])->name('theme-words.effect');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
