<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AduanMasyarakatController;
use App\Http\Controllers\Api\LayananDaruratController;
use App\Http\Controllers\Api\PengajuanSuratController;
use App\Http\Controllers\Api\PengajuanProposalController;
use App\Http\Controllers\Api\ProgramBantuanController;
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
    
    // Layanan Darurat (public, tidak perlu auth)
    Route::get('/layanan-darurat/kategori', [LayananDaruratController::class, 'getKategori']);
    Route::get('/layanan-darurat', [LayananDaruratController::class, 'index']);
});

// Protected routes untuk PWA (perlu auth)
Route::middleware('auth:sanctum')->prefix('pwa')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Dropdown options untuk form aduan (harus SEBELUM apiResource)
    Route::get('/aduan-masyarakat/kategori', [AduanMasyarakatController::class, 'getKategoriAduan']);
    
    // Route khusus untuk update dengan file (POST karena PUT tidak support multipart/form-data dengan baik)
    Route::post('/aduan-masyarakat/{id}/update', [AduanMasyarakatController::class, 'updateWithFiles']);
    
    // Aduan Masyarakat (Aduan Saya) - harus SETELAH route spesifik
    Route::apiResource('aduan-masyarakat', AduanMasyarakatController::class);
    
    // Layanan Surat - Pengajuan Saya
    Route::get('/pengajuan-surat/jenis-surat', [PengajuanSuratController::class, 'getJenisSurat']);
    Route::get('/pengajuan-surat/jenis-surat/{id}', [PengajuanSuratController::class, 'getJenisSuratDetail']);
    Route::get('/pengajuan-surat/{id}/export-pdf', [PengajuanSuratController::class, 'exportPdf']);
    Route::post('/pengajuan-surat/{id}/update', [PengajuanSuratController::class, 'update']); // POST untuk update dengan file
    Route::apiResource('pengajuan-surat', PengajuanSuratController::class);
    
    // Pengajuan Proposal - Proposal Saya
    Route::get('/pengajuan-proposal/kategori', [PengajuanProposalController::class, 'getKategoriProposal']);
    Route::get('/pengajuan-proposal/{id}/export-pdf', [PengajuanProposalController::class, 'exportPdf']);
    Route::post('/pengajuan-proposal/{id}/update', [PengajuanProposalController::class, 'update']); // POST untuk update dengan file
    Route::get('/pengajuan-proposal', [PengajuanProposalController::class, 'index']);
    Route::get('/pengajuan-proposal/{id}', [PengajuanProposalController::class, 'show']);
    Route::post('/pengajuan-proposal', [PengajuanProposalController::class, 'store']);
    
    // Program Bantuan - Riwayat Saya
    Route::get('/program-bantuan/riwayat-saya', [ProgramBantuanController::class, 'index']);
    Route::get('/program-bantuan/riwayat-saya/{id}', [ProgramBantuanController::class, 'show']);
});

// Existing routes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/users-menu', [UsersMenuController::class, 'getMenus']);

Route::get('/users', [UsersController::class, 'apiIndex']);
