<?php

use App\Http\Controllers\BriefController;
use App\Http\Controllers\DailyUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TeamUpdateController;
use App\Http\Middleware\EnsureTeamLead;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/daily-update', [DailyUpdateController::class, 'edit'])->name('daily-update.edit');
    Route::post('/daily-update', [DailyUpdateController::class, 'store'])->name('daily-update.store');
    Route::put('/daily-update', [DailyUpdateController::class, 'update'])->name('daily-update.update');

    Route::get('/briefs/today', [BriefController::class, 'today'])->name('briefs.today');
    Route::get('/briefs/{brief}', [BriefController::class, 'show'])->name('briefs.show');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/{brief}', [HistoryController::class, 'show'])->name('history.show');

    Route::middleware(EnsureTeamLead::class)->group(function () {
        Route::get('/team/updates', [TeamUpdateController::class, 'index'])->name('team.updates');
        Route::put('/briefs/{brief}', [BriefController::class, 'update'])->name('briefs.update');
        Route::post('/briefs/{brief}/publish', [BriefController::class, 'publish'])->name('briefs.publish');
    });
});
