<?php

use App\Http\Controllers\Planner\EventController;
use App\Http\Controllers\Planner\MilestoneController;
use App\Http\Controllers\Planner\PlannerController;
use App\Http\Controllers\Planner\PlannerExportController;
use App\Http\Controllers\Planner\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Planner main page
    Route::get('/planner', [PlannerController::class, 'index'])->name('planner');

    // Milestones
    Route::get('/milestones', [MilestoneController::class, 'index'])->name('milestones.index');
    Route::post('/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
    Route::get('/milestones/{milestone}', [MilestoneController::class, 'show'])->name('milestones.show');
    Route::put('/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
    Route::delete('/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');

    // Events
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event}/snooze', [EventController::class, 'snooze'])->name('events.snooze');

    // Tags
    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('/tags/{tag}/attach', [TagController::class, 'attach'])->name('tags.attach');
    Route::delete('/tags/{tag}/detach', [TagController::class, 'detach'])->name('tags.detach');

    // ICS export
    Route::get('/planner/export/ics', [PlannerExportController::class, 'fullPlanner'])->name('planner.export.ics');
    Route::get('/planner/export/ics/event/{event}', [PlannerExportController::class, 'singleEvent'])->name('planner.export.ics.event');
    Route::get('/planner/export/ics/milestone/{milestone}', [PlannerExportController::class, 'milestoneExport'])->name('planner.export.ics.milestone');
});
