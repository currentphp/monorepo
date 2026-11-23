<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'examples')->name('home');
Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
Route::middleware('can:view,project')->group(function (): void {
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::post('projects/{project}/rename', [ProjectController::class, 'rename'])->name('projects.rename');
    Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('projects/{project}/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('projects/{project}/tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
    Route::get('projects/{project}/tasks/{task}', [ProjectController::class, 'task'])->name('tasks.show');
    Route::get('api/projects/{project}/experience', [ProjectController::class, 'experience'])->name('projects.experience');
});

Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.index');
Route::get('onboarding/profile', [OnboardingController::class, 'show'])->name('onboarding.profile');
Route::post('onboarding/profile', [OnboardingController::class, 'profile'])->name('onboarding.profile.store');
Route::get('onboarding/workspace', [OnboardingController::class, 'show'])->name('onboarding.workspace');
Route::post('onboarding/workspace', [OnboardingController::class, 'workspace'])->name('onboarding.workspace.store');
Route::get('onboarding/workspaces/{workspace}', [OnboardingController::class, 'show'])->name('onboarding.review');
Route::post('onboarding/workspaces/{workspace}/finish', [OnboardingController::class, 'finish'])->name('onboarding.finish');
Route::post('onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');
