<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\BankSoalController;
use App\Http\Controllers\Api\Admin\SoalController;
use App\Http\Controllers\Api\Admin\TesController;
use App\Http\Controllers\Api\Admin\MateriController;

// ⬇️ IMPORT middleware class kita
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json(['ok' => true]));

// ========== AUTH ==========
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login',         [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',      [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// (Opsional) pendaftaran staff publik
Route::post('/register/staff', [UserManagementController::class, 'store']);

// ========== ADMIN (protected) ==========
// ⬇️ Pakai FQCN + parameter ':admin' (bukan alias)
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])
    ->prefix('admin')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Materi
        Route::get('/materi', [MateriController::class, 'index']);

        // Bank Soal
        Route::get('/bank-soal',         [BankSoalController::class, 'index']);
        Route::post('/bank-soal',        [BankSoalController::class, 'store']);
        Route::patch('/bank-soal/{id}',  [BankSoalController::class, 'update']);
        Route::delete('/bank-soal/{id}', [BankSoalController::class, 'destroy']);

        // Soal
        Route::get('/bank-soal/{bankId}/soal', [SoalController::class, 'listByBank']);
        Route::post('/soal',                    [SoalController::class, 'store']);
        Route::delete('/soal/{id}',             [SoalController::class, 'destroy']);

        // Tes
        Route::get('/tes',         [TesController::class, 'index']);
        Route::post('/tes',        [TesController::class, 'store']);
        Route::patch('/tes/{id}',  [TesController::class, 'update']);
        Route::delete('/tes/{id}', [TesController::class, 'destroy']);

        // Users
        Route::apiResource('users', UserManagementController::class);
    });
