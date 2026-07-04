<?php

use App\Http\Controllers\DailyUpdateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
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
});
