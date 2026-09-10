<?php

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BriefController;
use App\Http\Controllers\DailyUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TeamUpdateController;
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/daily-update', [DailyUpdateController::class, 'edit'])->name('daily-update.edit');
    Route::post('/daily-update', [DailyUpdateController::class, 'store'])->name('daily-update.store');
    Route::put('/daily-update', [DailyUpdateController::class, 'update'])->name('daily-update.update');

    Route::get('/briefs/today', [BriefController::class, 'today'])->name('briefs.today');
    Route::get('/briefs/{brief}', [BriefController::class, 'show'])->name('briefs.show');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{brief}', [HistoryController::class, 'show'])->name('history.show');

    Route::middleware(EnsureTeamLead::class)->group(function () {
        Route::get('/team/updates', [TeamUpdateController::class, 'index'])->name('team.updates');
        Route::get('/team/members/{user}', [TeamMemberController::class, 'show'])->name('team.members.show');
        Route::get('/briefs/{brief}/preview', [BriefController::class, 'preview'])->name('briefs.preview');
        Route::put('/briefs/{brief}', [BriefController::class, 'update'])->name('briefs.update');
        Route::post('/briefs/{brief}/publish', [BriefController::class, 'publish'])->name('briefs.publish');
    });
});
