<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Middleware\EnsureRegistrationEnabled;
use App\Http\Middleware\NormalizeEmail;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest:web')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware([NormalizeEmail::class, 'throttle:login'])
        ->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware([NormalizeEmail::class, 'throttle:password-recovery'])
        ->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])
        ->middleware([NormalizeEmail::class, 'throttle:password-reset'])
        ->name('password.update');

    Route::middleware(EnsureRegistrationEnabled::class)->group(function (): void {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store'])
            ->middleware([NormalizeEmail::class, 'throttle:registration'])
            ->name('register.store');
    });
});

Route::middleware('auth:web')->group(function (): void {
    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:verification'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:verification')->name('verification.send');
    Route::middleware('verified')->group(function (): void {
        Route::inertia('dashboard', 'dashboard')->name('dashboard');
        Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::middleware('auth:web')->group(function (): void {
    Route::get('projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('projects/{project}/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('projects/{project}/tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
});
Route::get('projects/{project}/tasks/{task}', [ProjectController::class, 'task'])->name('tasks.show');
