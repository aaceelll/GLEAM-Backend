<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthController;

// Admin
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// (Opsional) endpoint cek server sederhana
Route::get('/health', fn () => response()->json(['ok' => true]));

// ==========================
// AUTH
// ==========================
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ==========================
// ADMIN (protected)
// ==========================
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])
    ->prefix('admin')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // User Management (CRUD)
        Route::apiResource('users', UserManagementController::class);
    });
