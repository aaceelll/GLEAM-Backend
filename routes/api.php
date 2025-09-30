<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\BankSoalController;
use App\Http\Controllers\Api\Admin\SoalController;
use App\Http\Controllers\Api\Admin\TesController;
use App\Http\Controllers\Api\Admin\MateriController;

// Forum Controller
use App\Http\Controllers\Api\ForumController;

// Middleware
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
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ========== PROFILE (protected) ==========
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

// (Opsional) pendaftaran staff publik
Route::post('/register/staff', [UserManagementController::class, 'store']);

// ========== ADMIN (protected) ==========
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])
    ->prefix('admin')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Materi
        Route::get('/materi', [MateriController::class, 'index']);

        // Bank Soal
        Route::get('/bank-soal', [BankSoalController::class, 'index']);
        Route::post('/bank-soal', [BankSoalController::class, 'store']);
        Route::patch('/bank-soal/{id}', [BankSoalController::class, 'update']);
        Route::delete('/bank-soal/{id}', [BankSoalController::class, 'destroy']);

        // Soal
        Route::get('/bank-soal/{bankId}/soal', [SoalController::class, 'listByBank']);
        Route::post('/soal', [SoalController::class, 'store']);
        Route::delete('/soal/{id}', [SoalController::class, 'destroy']);

        // Tes
        Route::get('/tes', [TesController::class, 'index']);
        Route::post('/tes', [TesController::class, 'store']);
        Route::patch('/tes/{id}', [TesController::class, 'update']);
        Route::delete('/tes/{id}', [TesController::class, 'destroy']);

        // Users
        Route::apiResource('users', UserManagementController::class);

        // ===== ADMIN FORUM MANAGEMENT (PUBLIC ONLY) =====
        Route::prefix('forum')->group(function () {
            Route::post('/threads/{id}/pin', [ForumController::class, 'pinThread']);
            Route::post('/threads/{id}/lock', [ForumController::class, 'lockThread']);
            Route::delete('/threads/{id}/force', [ForumController::class, 'forceDeleteThread']);
        });
    });

// ========== FORUM ROUTES (protected) ==========
Route::middleware('auth:sanctum')->prefix('forum')->group(function () {
    // Categories
    Route::get('/categories', [ForumController::class, 'getCategories']);
    
    // Threads - PUBLIC & PRIVATE
    Route::get('/threads', [ForumController::class, 'getThreads']); // Support filter type=public|private
    Route::get('/threads/{id}', [ForumController::class, 'getThreadDetail']);
    Route::post('/threads', [ForumController::class, 'createThread']); // Bisa public atau private
    Route::delete('/threads/{id}', [ForumController::class, 'deleteThread']);
    
    // Replies
    Route::post('/threads/{id}/reply', [ForumController::class, 'replyThread']);
    Route::delete('/replies/{id}', [ForumController::class, 'deleteReply']);
    
    // Likes
    Route::post('/threads/{id}/like', [ForumController::class, 'likeThread']);
    Route::post('/replies/{id}/like', [ForumController::class, 'likeReply']);
    
    // ===== NAKES ONLY: Private Questions Management =====
    Route::get('/private/pending', [ForumController::class, 'getPendingPrivateThreads']);
    Route::get('/private/my-assignments', [ForumController::class, 'getMyPrivateThreads']);
    Route::post('/threads/{id}/assign', [ForumController::class, 'assignToSelf']);
    
    // Close thread (User, Nakes, or Admin)
    Route::patch('/threads/{id}/close', [ForumController::class, 'closeThread']);
});