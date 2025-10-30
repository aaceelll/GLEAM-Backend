<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ProfileController;
use App\Http\Controllers\api\WebsiteReviewController;
use App\Http\Controllers\api\Admin\DashboardController;
use App\Http\Controllers\api\Admin\UserManagementController;
use App\Http\Controllers\api\Admin\BankSoalController;
use App\Http\Controllers\api\Admin\SoalController;
use App\Http\Controllers\api\Admin\TesController;
use App\Http\Controllers\api\Admin\MateriController;
use App\Http\Controllers\api\Nakes\ScreeningController;
use App\Http\Controllers\api\ForumController;
use App\Http\Controllers\api\user\QuizController;
use App\Http\Controllers\api\user\QuizSubmissionController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\api\LocationController;
use App\Http\Controllers\api\user\MyScreeningController;
use App\Http\Controllers\api\PatientController;
use App\Http\Controllers\api\Manajemen\DashboardController as ManajemenDashboardController;
use App\Http\Controllers\api\user\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;


/* Health */
Route::get('/health', fn () => response()->json(['ok' => true]));

Route::get('/patients/search', [PatientController::class, 'search']);
Route::post('/screenings', [ScreeningController::class, 'store']);
Route::get('/screenings/latest', [ScreeningController::class, 'latest']);

/* Auth */
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
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
        // Dashboard User: glukosa terakhir & waktu
        Route::get('/dashboard/summary', [UserDashboardController::class, 'summary']);
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
        Route::get('/materi/konten/{id}/download', [MateriController::class, 'downloadKonten']);
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
            Route::get('/submissions', [\App\Http\Controllers\Api\user\QuizSubmissionController::class, 'allSubmissions']);
            Route::get('/submissions/{id}', [\App\Http\Controllers\Api\user\QuizSubmissionController::class, 'submissionDetail']);
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
    
    // 🆕 UPDATE: Ganti route lama dengan yang baru
    // Route lama (akan di-comment atau dihapus):
    // Route::patch('/threads/{id}/close', [ForumController::class, 'closeThread']);
    
    // 🆕 Routes baru untuk toggle lock/unlock dan delete private thread
    Route::patch('/threads/{id}/toggle-lock', [ForumController::class, 'toggleLockThread']);
    Route::delete('/threads/{id}/private', [ForumController::class, 'deletePrivateThread']);
});

/* Location - Dashboard Manajemen */
Route::middleware('auth:sanctum')->prefix('locations')->group(function () {
    Route::get('/users', [LocationController::class, 'getUsersWithLocations']);
    Route::get('/statistics', [LocationController::class, 'getStatistics']);
    Route::get('/users-by-rw', [LocationController::class, 'getUsersByRW']);
    Route::get('/user/{id}', [LocationController::class, 'getUserDetail']);
});


Route::get('/storage/materi/{filename}', function ($filename) {
    $path = storage_path('public_html/storage/app/public/materi/' . $filename);

    if (!File::exists($path)) {
        abort(404, 'File tidak ditemukan.');
    }

    $mime = File::mimeType($path);

    return Response::make(File::get($path), 200, [
        'Content-Type' => $mime,
        'Access-Control-Allow-Origin' => '*', // biar tidak kena CORS
    ]);
});