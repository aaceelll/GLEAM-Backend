<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WebsiteReviewController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\BankSoalController;
use App\Http\Controllers\Api\Admin\SoalController;
use App\Http\Controllers\Api\Admin\TesController;
use App\Http\Controllers\Api\Admin\MateriController;
use App\Http\Controllers\Api\Nakes\ScreeningController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\User\QuizController;
use App\Http\Controllers\Api\User\QuizSubmissionController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\User\MyScreeningController;
use App\Http\Controllers\Api\Manajemen\DashboardController as ManajemenDashboardController;

/* Health */
Route::get('/health', fn () => response()->json(['ok' => true]));

/* Auth */
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::post('/register/staff', [UserManagementController::class, 'store']);

/* Public (contoh materi) */
Route::get('/materi/konten', [MateriController::class, 'listKontenPublic']);
Route::get('/materi/tes/{id}', [MateriController::class, 'showTesPublic']);
Route::get('/materi/tes-by-bank/{bankId}', [MateriController::class, 'showTesByBank']);

/* Authenticated (umum) */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::put('/profile/personal-info', [ProfileController::class, 'updatePersonalInfo']);

    Route::get('/website-review', [WebsiteReviewController::class, 'show']);
    Route::post('/website-review', [WebsiteReviewController::class, 'store']);

    /* User */
    Route::prefix('user')->group(function () {
        Route::get('/tests', [QuizController::class, 'getAvailableTests']);
        Route::get('/tests/{testId}', [QuizController::class, 'getTestDetail']);
    });

    // untuk ambil riwayat screening user sesuai akun yang login
    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        Route::get('diabetes-screenings', [MyScreeningController::class, 'index']);
        Route::get('diabetes-screenings/{id}', [MyScreeningController::class, 'show']);
    });

    // === NEW: bank soal publish & aktif yang terhubung ke materi (by slug / materi_id)
    Route::get('/quiz/banks/by-materi', [QuizController::class, 'banksByMateri']);

    Route::get('/quiz/banks', [QuizController::class, 'banksDefault']);
    Route::get('/quiz/banks/all', [QuizController::class, 'getAllActiveBanks']);
    Route::get('/quiz/banks/{bank}', [QuizController::class, 'listSoalPublic']);
    Route::get('/quiz/banks/{bankId}/detail', [QuizController::class, 'getBankDetail']);
    Route::post('/quiz/submit', [QuizSubmissionController::class, 'submit']);
    Route::get('/quiz/history', [QuizSubmissionController::class, 'history']);
    Route::get('/quiz/history/{id}', [QuizSubmissionController::class, 'detail']);
});

/* Nakes  */
Route::middleware('auth:sanctum')->prefix('nakes')->group(function () {
    Route::get('diabetes-screenings', [ScreeningController::class, 'index']);
    Route::get('diabetes-screenings/{id}', [ScreeningController::class, 'show']);
    Route::get('users/{userId}/diabetes-screenings', [ScreeningController::class, 'byUser']);
});

/* Admin */
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/materi', [MateriController::class, 'index']);
        Route::get('/materi/konten', [MateriController::class, 'listKonten']);
        Route::post('/materi/konten', [MateriController::class, 'storeKonten']);
        Route::patch('/materi/konten/{id}', [MateriController::class, 'updateKonten']);
        Route::delete('/materi/konten/{id}', [MateriController::class, 'destroyKonten']);
        Route::get('/bank-soal', [BankSoalController::class, 'index']);
        Route::post('/bank-soal', [BankSoalController::class, 'store']);
        Route::patch('/bank-soal/{id}', [BankSoalController::class, 'update']);
        Route::delete('/bank-soal/{id}', [BankSoalController::class, 'destroy']);
        Route::get('/bank-soal/{bankId}/soal', [SoalController::class, 'listByBank']);
        Route::post('/bank-soal/{bankId}/soal', [SoalController::class, 'store']);
        Route::post('/soal', [SoalController::class, 'store']);
        Route::delete('/soal/{id}', [SoalController::class, 'destroy']);
        Route::get('/tes', [TesController::class, 'index']);
        Route::post('/tes', [TesController::class, 'store']);
        Route::patch('/tes/{id}', [TesController::class, 'update']);
        Route::delete('/tes/{id}', [TesController::class, 'destroy']);
        Route::post('/tests/publish-from-bank', [TesController::class, 'publishFromBank']);
        Route::apiResource('users', UserManagementController::class);
        Route::prefix('forum')->group(function () {
            Route::post('/threads/{id}/pin', [ForumController::class, 'pinThread']);
            Route::post('/threads/{id}/lock', [ForumController::class, 'lockThread']);
            Route::delete('/threads/{id}/force', [ForumController::class, 'forceDeleteThread']);
        });
        
        // Quiz Submissions untuk Admin
        Route::prefix('quiz')->group(function () {
            Route::get('/submissions', [QuizSubmissionController::class, 'allSubmissions']);
            Route::get('/submissions/{id}', [QuizSubmissionController::class, 'submissionDetail']);
        });
    });

Route::middleware(['auth:sanctum', \App\Http\Middleware\RoleMiddleware::class . ':manajemen'])
    ->prefix('manajemen')
    ->group(function () {
        // Statistik beranda manajemen
        Route::get('/statistics', [ManajemenDashboardController::class, 'statistics']);

        // Quiz Submissions (punyamu tadi)
        Route::prefix('quiz')->group(function () {
            Route::get('/submissions', [\App\Http\Controllers\Api\User\QuizSubmissionController::class, 'allSubmissions']);
            Route::get('/submissions/{id}', [\App\Http\Controllers\Api\User\QuizSubmissionController::class, 'submissionDetail']);
        });
    });


/* Forum */
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

/* Location - Dashboard Manajemen */
Route::middleware('auth:sanctum')->prefix('locations')->group(function () {
    Route::get('/users', [LocationController::class, 'getUsersWithLocations']);
    Route::get('/statistics', [LocationController::class, 'getStatistics']);
    Route::get('/users-by-rw', [LocationController::class, 'getUsersByRW']);
    Route::get('/user/{id}', [LocationController::class, 'getUserDetail']);
});