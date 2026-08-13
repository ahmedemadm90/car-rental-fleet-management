<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\OwnerCarController;
use App\Http\Controllers\Api\PublicCarController;
use App\Http\Controllers\Api\RentalShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/cars', [PublicCarController::class, 'index']);
    Route::get('/cars/{car}', [PublicCarController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/bookings/me', [BookingController::class, 'myBookings']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        Route::get('/owner/dashboard', [DashboardController::class, 'summary']);
        Route::get('/owner/shops', [RentalShopController::class, 'index']);
        Route::post('/owner/shops', [RentalShopController::class, 'store']);
        Route::patch('/owner/shops/{shop}', [RentalShopController::class, 'update']);

        Route::get('/owner/shops/{shop}/cars', [OwnerCarController::class, 'index']);
        Route::post('/owner/shops/{shop}/cars', [OwnerCarController::class, 'store']);
        Route::get('/owner/cars/{car}', [OwnerCarController::class, 'show']);
        Route::patch('/owner/cars/{car}', [OwnerCarController::class, 'update']);
        Route::delete('/owner/cars/{car}', [OwnerCarController::class, 'destroy']);

        Route::get('/owner/cars/{car}/expenses', [ExpenseController::class, 'index']);
        Route::post('/owner/cars/{car}/expenses', [ExpenseController::class, 'store']);
        Route::delete('/owner/expenses/{expense}', [ExpenseController::class, 'destroy']);

        Route::get('/owner/cars/{car}/maintenance', [MaintenanceController::class, 'index']);
        Route::post('/owner/cars/{car}/maintenance', [MaintenanceController::class, 'store']);
        Route::delete('/owner/maintenance/{maintenance}', [MaintenanceController::class, 'destroy']);

        Route::get('/owner/bookings', [BookingController::class, 'ownerIndex']);
        Route::patch('/owner/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
    });
});
