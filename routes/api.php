<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\BabyController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\UserController; // Pastikan Controller ini ada

/*
|--------------------------------------------------------------------------
| Public Routes (Akses Tanpa Login)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

// Guest bisa melakukan deteksi (opsional, tergantung kebijakan Anda)
Route::post('/detect-cry-guest', [HistoryController::class, 'storeGuest']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Wajib Mengirimkan Token Bearer)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 1. Profil & Auth
    Route::get('/user', [AuthController::class, 'userProfile']); // Wajib untuk fetchAllData()
    Route::post('/logout', [AuthController::class, 'logout']);

    // 2. Fitur Khusus Owner (Manajemen Akun)
    // Digunakan owner untuk mengisi dropdown dan membuat akun orang tua
    Route::get('/users-parents', [ParentController::class, 'index']);
    Route::post('/users', [ParentController::class, 'store']);
    Route::put('/users/{id}', [ParentController::class, 'update']);
    Route::delete('/users/{id}', [ParentController::class, 'destroy']);

    // 3. Manajemen Bayi
    // Index akan otomatis memfilter: Owner (semua), Parent (miliknya)
    Route::get('/babies', [BabyController::class, 'index']);
    Route::post('/babies', [BabyController::class, 'store']);
    Route::get('/babies/{id}', [BabyController::class, 'show']);
    Route::put('/babies/{id}', [BabyController::class, 'update']);
    Route::delete('/babies/{id}', [BabyController::class, 'destroy']);

    // 4. Riwayat Tangisan (History)
    // Sesuaikan penamaan dengan Flutter: /cry-records
    Route::get('/cry-records', [HistoryController::class, 'index']);
    Route::post('/detect-cry', [HistoryController::class, 'store']); // Untuk simpan hasil deteksi
    Route::delete('/cry-records/{id}', [HistoryController::class, 'destroy']);

    // 5. Statistik Dashboard
    Route::get('/statistics', [HistoryController::class, 'getAdvancedStats']);

    // 6. Rekomendasi
    Route::get('/recommendations', [RecommendationController::class, 'index']);

    // 7. Fitur Edit Profile (Khusus Parent)
    Route::put('/user/update', [UserController::class, 'updateProfile']);

    Route::get('/daily-stats', [HistoryController::class, 'getDailyStats']);
});
