<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use App\Http\Middleware\JwtAuthenticate;
use Illuminate\Support\Facades\Route;

// --- Auth routes (public) ---
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// --- Protected routes ---
Route::middleware(JwtAuthenticate::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    Route::prefix('travel-orders')->group(function () {
        Route::get('/',           [TravelOrderController::class, 'index']);
        Route::post('/',          [TravelOrderController::class, 'store']);
        Route::get('/{id}',       [TravelOrderController::class, 'show']);
        Route::patch('/{id}/status', [TravelOrderController::class, 'updateStatus']);
    });
});
