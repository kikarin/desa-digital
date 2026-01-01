<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AduanMasyarakatController;
use App\Http\Controllers\UsersMenuController;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes untuk PWA (tidak perlu auth)
Route::prefix('pwa')->group(function () {
    // Auth routes untuk PWA
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes untuk PWA (perlu auth)
Route::middleware('auth:sanctum')->prefix('pwa')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Dropdown options untuk form aduan (harus SEBELUM apiResource)
    Route::get('/aduan-masyarakat/kategori', [AduanMasyarakatController::class, 'getKategoriAduan']);
    Route::get('/aduan-masyarakat/kecamatan', [AduanMasyarakatController::class, 'getKecamatan']);
    Route::get('/aduan-masyarakat/desa/{kecamatanId}', [AduanMasyarakatController::class, 'getDesa']);
    
    // Aduan Masyarakat (Aduan Saya) - harus SETELAH route spesifik
    Route::apiResource('aduan-masyarakat', AduanMasyarakatController::class);
});

// Existing routes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/users-menu', [UsersMenuController::class, 'getMenus']);

Route::get('/users', [UsersController::class, 'apiIndex']);
