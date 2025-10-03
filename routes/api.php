<?php

use Illuminate\Support\Facades\Route;

// ===== Controllers =====
// Auth
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WebsiteReviewController;

// Admin
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\BankSoalController;
use App\Http\Controllers\Api\Admin\SoalController;
use App\Http\Controllers\Api\Admin\TesController;
use App\Http\Controllers\Api\Admin\MateriController;

// Forum
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\User\QuizController;
// Middleware
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json(['ok' => true]));

// ========================= AUTH =========================
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ============== PROFILE & MISC (protected) ==============
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::put('/profile/personal-info', [ProfileController::class, 'updatePersonalInfo']);

    // Website Review
    Route::get('/website-review', [WebsiteReviewController::class, 'show']);
    Route::post('/website-review', [WebsiteReviewController::class, 'store']);
});

// (opsional) pendaftaran staff publik
Route::post('/register/staff', [UserManagementController::class, 'store']);

// ====================== PUBLIC (USER) ======================
// Materi untuk halaman user (tanpa prefix /admin)
Route::get('/materi/konten', [MateriController::class, 'listKontenPublic']); // ?slug=diabetes-melitus
Route::get('/materi/tes/{id}', [MateriController::class, 'showTesPublic']);
Route::get('/materi/tes-by-bank/{bankId}', [MateriController::class, 'showTesByBank']); // detail langsung dari bank (fallback)
Route::get('/materi/tes/{id}', [MateriController::class, 'showTesByBank']);

Route::prefix('admin')->group(function () {
    Route::get('/tes', [TesController::class, 'index']);
    Route::post('/tes', [TesController::class, 'store']);
    Route::patch('/tes/{id}', [TesController::class, 'update']);
    Route::delete('/tes/{id}', [TesController::class, 'destroy']);
    Route::post('/admin/tests/from-bank', [TesController::class, 'createFromBank']);
});

Route::middleware('auth:sanctum')->group(function () {
    // ... routes lainnya
    
    Route::prefix('user')->group(function () {
        // ✅ TAMBAHKAN INI
        Route::get('/tests', [QuizController::class, 'getAvailableTests']);
        Route::get('/tests/{testId}', [QuizController::class, 'getTestDetail']);
    });

    // Quiz routes
        Route::get('/quiz/banks', [QuizController::class, 'banksDefault']);
        Route::get('/quiz/banks/all', [QuizController::class, 'getAllActiveBanks']);
        Route::get('/quiz/banks/{bank}', [QuizController::class, 'listSoalPublic']);
        Route::get('/quiz/banks/{bankId}/detail', [QuizController::class, 'getBankDetail']);
});

// ====================== ADMIN (protected) ======================
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])
    ->prefix('admin')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // -------- Materi (ADMIN) --------
        Route::get('/materi', [MateriController::class, 'index']);
        Route::get('/materi/konten', [MateriController::class, 'listKonten']);     // list untuk admin
        Route::post('/materi/konten', [MateriController::class, 'storeKonten']);
        Route::patch('/materi/konten/{id}', [MateriController::class, 'updateKonten']);
        Route::delete('/materi/konten/{id}', [MateriController::class, 'destroyKonten']);

        // -------- Bank Soal --------
        Route::get('/bank-soal', [BankSoalController::class, 'index']);
        Route::post('/bank-soal', [BankSoalController::class, 'store']);
        Route::patch('/bank-soal/{id}', [BankSoalController::class, 'update']);
        Route::delete('/bank-soal/{id}', [BankSoalController::class, 'destroy']);

        // -------- Soal (nested di Bank Soal) --------
        Route::get('/bank-soal/{bankId}/soal', [SoalController::class, 'listByBank']);  // list soal per bank
        Route::post('/bank-soal/{bankId}/soal', [SoalController::class, 'store']);      // preferred: kirim ke bank tertentu

        // (legacy / kompatibilitas) tetap terima POST /soal
        Route::post('/soal', [SoalController::class, 'store']);
        Route::delete('/soal/{id}', [SoalController::class, 'destroy']);

        // -------- Tes --------
        Route::get('/tes', [TesController::class, 'index']);
        Route::post('/tes', [TesController::class, 'store']);
        Route::patch('/tes/{id}', [TesController::class, 'update']);
        Route::delete('/tes/{id}', [TesController::class, 'destroy']);
        Route::post('/tests/publish-from-bank', [TesController::class, 'publishFromBank']);

        // -------- Users --------
        Route::apiResource('users', UserManagementController::class);

        // -------- Forum Management --------
        Route::prefix('forum')->group(function () {
            Route::post('/threads/{id}/pin', [ForumController::class, 'pinThread']);
            Route::post('/threads/{id}/lock', [ForumController::class, 'lockThread']);
            Route::delete('/threads/{id}/force', [ForumController::class, 'forceDeleteThread']);
        });
    });

// ===================== FORUM (protected) =====================
Route::middleware('auth:sanctum')->prefix('forum')->group(function () {
    Route::get('/categories', [ForumController::class, 'getCategories']);
    Route::get('/threads', [ForumController::class, 'getThreads']);
    Route::get('/threads/{id}', [ForumController::class, 'getThreadDetail']);
    Route::post('/threads', [ForumController::class, 'createThread']);
    Route::delete('/threads/{id}', [ForumController::class, 'deleteThread']);
    Route::post('/threads/{id}/reply', [ForumController::class, 'replyThread']);
    Route::delete('/replies/{id}', [ForumController::class, 'deleteReply']);
    Route::post('/threads/{id}/like', [ForumController::class, 'likeThread']);
    Route::post('/replies/{id}', [ForumController::class, 'likeReply']);
    Route::get('/private/pending', [ForumController::class, 'getPendingPrivateThreads']);
    Route::get('/private/my-assignments', [ForumController::class, 'getMyPrivateThreads']);
    Route::post('/threads/{id}/assign', [ForumController::class, 'assignToSelf']);
    Route::patch('/threads/{id}/close', [ForumController::class, 'closeThread']);
});
