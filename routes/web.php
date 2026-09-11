<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminProjectController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BriefController;
use App\Http\Controllers\DailyUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberTaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TeamUpdateController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotAdmin;
use App\Http\Middleware\EnsureTeamLead;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(EnsureAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}/teams', [AdminProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}/teams', [AdminProjectController::class, 'updateTeams'])->name('projects.update-teams');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware(EnsureNotAdmin::class)->group(function () {
        Route::get('/daily-update', [DailyUpdateController::class, 'edit'])->name('daily-update.edit');
        Route::get('/daily-update/history', [DailyUpdateController::class, 'history'])->name('daily-update.history');
        Route::post('/daily-update', [DailyUpdateController::class, 'store'])->name('daily-update.store');
        Route::put('/daily-update', [DailyUpdateController::class, 'update'])->name('daily-update.update');

        Route::get('/my-tasks', [MemberTaskController::class, 'index'])->name('tasks.my');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');

        Route::get('/briefs/today', [BriefController::class, 'today'])->name('briefs.today');
        Route::get('/briefs/{brief}', [BriefController::class, 'show'])->name('briefs.show');
        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/{brief}', [HistoryController::class, 'show'])->name('history.show');
    });

    Route::middleware(EnsureTeamLead::class)->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('/projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

        Route::get('/team/updates', [TeamUpdateController::class, 'index'])->name('team.updates');
        Route::get('/team/members/{user}', [TeamMemberController::class, 'show'])->name('team.members.show');
        Route::get('/briefs/{brief}/preview', [BriefController::class, 'preview'])->name('briefs.preview');
        Route::put('/briefs/{brief}', [BriefController::class, 'update'])->name('briefs.update');
        Route::post('/briefs/{brief}/publish', [BriefController::class, 'publish'])->name('briefs.publish');
    });
});
