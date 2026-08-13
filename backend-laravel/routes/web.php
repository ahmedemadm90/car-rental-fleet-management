<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/fleet', [DashboardController::class, 'fleet'])->name('fleet');
    Route::get('/bookings', [DashboardController::class, 'bookings'])->name('bookings');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
