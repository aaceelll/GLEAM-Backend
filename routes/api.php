<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ProfileController;
use App\Http\Controllers\api\WebsiteReviewController;
use App\Http\Controllers\api\Admin\DashboardController;
use App\Http\Controllers\api\Admin\UserManagementController;
use App\Http\Controllers\api\Admin\BankSoalController;
use App\Http\Controllers\api\Admin\SoalController;
use App\Http\Controllers\api\Admin\MateriController;
use App\Http\Controllers\api\Nakes\ScreeningController;
use App\Http\Controllers\api\ForumController;
use App\Http\Controllers\api\user\QuizController;
use App\Http\Controllers\api\user\QuizSubmissionController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\api\Manajemen\LocationController as ManajemenLocationController;
use App\Http\Controllers\api\user\MyScreeningController;
use App\Http\Controllers\api\PatientController;
use App\Http\Controllers\api\Manajemen\DashboardController as ManajemenDashboardController;
use App\Http\Controllers\api\Manajemen\WebsiteReviewController as ManajemenWebsiteReviewController;
use App\Http\Controllers\api\user\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

/* Autentikasi All Role */
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/login', [AuthController::class, 'login']);
    //reset password
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // logout
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// register staff (nakes, manajemen, admin)
Route::post('/register/staff', [UserManagementController::class, 'store']);

/* Health Role User*/
Route::get('/health', fn () => response()->json(['ok' => true]));
Route::get('/patients/search', [PatientController::class, 'search']);
Route::post('/screenings', [ScreeningController::class, 'store']);
Route::get('/screenings/latest', [ScreeningController::class, 'latest']);

/* Konten Materi Role User */
Route::get('/materi/konten', [MateriController::class, 'listKontenPublic']);
Route::get('/materi/tes/{id}', [MateriController::class, 'showTesPublic']);
Route::get('/materi/tes-by-bank/{bankId}', [MateriController::class, 'showTesByBank']);

/* Personal Info Role User */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::put('/profile/personal-info', [ProfileController::class, 'updatePersonalInfo']);

    // Website Review Role User
    Route::get('/website-review', [WebsiteReviewController::class, 'show']);
    Route::post('/website-review', [WebsiteReviewController::class, 'store']);

    // Quiz Role User (cek lagi)
    Route::prefix('user')->group(function () {
        Route::get('/tests', [QuizController::class, 'getAvailableTests']);
        Route::get('/tests/{testId}', [QuizController::class, 'getTestDetail']);
    });

    // Riwayat Screening Role User
    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        Route::get('diabetes-screenings', [MyScreeningController::class, 'index']);
        Route::get('diabetes-screenings/{id}', [MyScreeningController::class, 'show']);
        // Dashboard User: glukosa terakhir & waktu
        Route::get('/dashboard/summary', [UserDashboardController::class, 'summary']);
    });

    // Kuesioner Role User
    Route::get('/quiz/banks/by-materi', [QuizController::class, 'banksByMateri']);
    Route::get('/quiz/banks', [QuizController::class, 'banksDefault']);
    Route::get('/quiz/banks/all', [QuizController::class, 'getAllActiveBanks']);
    Route::get('/quiz/banks/{bank}', [QuizController::class, 'listSoalPublic']);
    Route::get('/quiz/banks/{bankId}/detail', [QuizController::class, 'getBankDetail']);
    Route::post('/quiz/submit', [QuizSubmissionController::class, 'submit']);
    Route::get('/quiz/history', [QuizSubmissionController::class, 'history']);
    Route::get('/quiz/history/{id}', [QuizSubmissionController::class, 'detail']);
});

/* Role Nakes  */
Route::middleware('auth:sanctum')->prefix('nakes')->group(function () {
    Route::get('diabetes-screenings', [ScreeningController::class, 'index']);
    Route::get('diabetes-screenings/{id}', [ScreeningController::class, 'show']);
    Route::get('users/{userId}/diabetes-screenings', [ScreeningController::class, 'byUser']);
});

/* ROLE ADMIN */
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])
    ->prefix('admin')
    ->group(function () {
        // dashboard admin
        Route::get('/dashboard', [DashboardController::class, 'index']);
        // materi dan konten materi
        Route::get('/materi', [MateriController::class, 'index']);
        Route::get('/materi/konten', [MateriController::class, 'listKonten']);
        Route::post('/materi/konten', [MateriController::class, 'storeKonten']);
        Route::patch('/materi/konten/{id}', [MateriController::class, 'updateKonten']);
        Route::delete('/materi/konten/{id}', [MateriController::class, 'destroyKonten']);
        Route::get('/materi/konten/{id}/download', [MateriController::class, 'downloadKonten']);
        // bank soal dan soal
        Route::get('/bank-soal', [BankSoalController::class, 'index']);
        Route::post('/bank-soal', [BankSoalController::class, 'store']);
        Route::patch('/bank-soal/{id}', [BankSoalController::class, 'update']);
        Route::delete('/bank-soal/{id}', [BankSoalController::class, 'destroy']);
        Route::get('/bank-soal/{bankId}/soal', [SoalController::class, 'listByBank']);
        Route::post('/bank-soal/{bankId}/soal', [SoalController::class, 'store']);
        Route::delete('/soal/{id}', [SoalController::class, 'destroy']);
        // akun dan peran
        Route::apiResource('users', UserManagementController::class);
        // forum untuk admin
        Route::prefix('forum')->group(function () {
            Route::post('/threads/{id}/pin', [ForumController::class, 'pinThread']);
            Route::post('/threads/{id}/lock', [ForumController::class, 'lockThread']);
            Route::delete('/threads/{id}/force', [ForumController::class, 'forceDeleteThread']);
        });
    });

/* ROLE MANAJEMEN */
Route::middleware(['auth:sanctum', \App\Http\Middleware\RoleMiddleware::class . ':manajemen'])
    ->prefix('manajemen')
    ->group(function () {
        // Statistik beranda manajemen
        Route::get('/statistics', [ManajemenDashboardController::class, 'statistics']);
        // Website Reviews
        Route::get('/website-reviews', [ManajemenWebsiteReviewController::class, 'index']);
        Route::get('/website-reviews/user/{userId}/history', [ManajemenWebsiteReviewController::class, 'userHistory']); 
        Route::get('/website-reviews/{id}', [ManajemenWebsiteReviewController::class, 'show']); 
        // Quiz Submissions 
        Route::prefix('quiz')->group(function () {
            Route::get('/submissions', [QuizSubmissionController::class, 'allSubmissions']);
            Route::get('/submissions/{id}', [QuizSubmissionController::class, 'submissionDetail']);
        });
    });

/* Location - Role Manajemen */
Route::middleware('auth:sanctum')->prefix('locations')->group(function () {
    Route::get('/users', [ManajemenLocationController::class, 'getUsersWithLocations']);
    Route::get('/statistics', [ManajemenLocationController::class, 'getStatistics']);
    Route::get('/users-by-rw', [ManajemenLocationController::class, 'getUsersByRW']);
    Route::get('/user/{id}', [ManajemenLocationController::class, 'getUserDetail']);
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
    // Route::post('/threads/{id}/like', [ForumController::class, 'likeThread']);
    Route::post('/replies/{id}', [ForumController::class, 'likeReply']);
    Route::get('/private/pending', [ForumController::class, 'getPendingPrivateThreads']);
    Route::get('/private/my-assignments', [ForumController::class, 'getMyPrivateThreads']);
    Route::post('/threads/{id}/assign', [ForumController::class, 'assignToSelf']);
    
    // Routes baru untuk toggle lock/unlock dan delete private thread
    Route::patch('/threads/{id}/toggle-lock', [ForumController::class, 'toggleLockThread']);
    Route::delete('/threads/{id}/private', [ForumController::class, 'deletePrivateThread']);
});

// Public dari Laravel - Serve konten materi dari storage dengan CORS headers
Route::get('/storage/materi/{filename}', function ($filename) {
    $path = storage_path('public_html/storage/app/public/materi/' . $filename);

    if (!File::exists($path)) {
        abort(404, 'File tidak ditemukan.');
    }

    $mime = File::mimeType($path);

    return Response::make(File::get($path), 200, [
        'Content-Type' => $mime,
        'Access-Control-Allow-Origin' => '*', 
    ]);
});