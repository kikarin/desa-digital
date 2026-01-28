<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AduanMasyarakatController;
use App\Http\Controllers\Api\LayananDaruratController;
use App\Http\Controllers\Api\PengajuanSuratController;
use App\Http\Controllers\Api\PengajuanProposalController;
use App\Http\Controllers\Api\ProgramBantuanController;
use App\Http\Controllers\Api\BeritaPengumumanController;
use App\Http\Controllers\Api\HouseController;
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
    
    // Berita Pengumuman (public, tidak perlu auth)
    Route::get('/berita-pengumuman/navbar', [BeritaPengumumanController::class, 'getNavbar']);
    Route::get('/berita-pengumuman/tipe', [BeritaPengumumanController::class, 'getTipe']);
    Route::get('/berita-pengumuman', [BeritaPengumumanController::class, 'index']);
    Route::get('/berita-pengumuman/{id}', [BeritaPengumumanController::class, 'show']);
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

    // Pengajuan Surat - Verifikasi RT (PWA, khusus role RT)
    Route::get('/pengajuan-surat-rt', [PengajuanSuratController::class, 'indexRtPwa']);
    Route::get('/pengajuan-surat-rt/{id}', [PengajuanSuratController::class, 'showRtPwa']);
    Route::post('/pengajuan-surat-rt/{id}/verifikasi', [PengajuanSuratController::class, 'verifyRtPwa']);
    
    // Pengajuan Proposal - Proposal Saya
    Route::get('/pengajuan-proposal/kategori', [PengajuanProposalController::class, 'getKategoriProposal']);
    Route::get('/pengajuan-proposal/template', [PengajuanProposalController::class, 'getTemplateProposal']);
    Route::get('/pengajuan-proposal/{id}/export-pdf', [PengajuanProposalController::class, 'exportPdf']);
    Route::post('/pengajuan-proposal/{id}/update', [PengajuanProposalController::class, 'update']); 
    Route::get('/pengajuan-proposal', [PengajuanProposalController::class, 'index']);
    Route::get('/pengajuan-proposal/{id}', [PengajuanProposalController::class, 'show']);
    Route::post('/pengajuan-proposal', [PengajuanProposalController::class, 'store']);
    
    // Program Bantuan - Riwayat Saya
    Route::get('/program-bantuan/riwayat-saya', [ProgramBantuanController::class, 'index']);
    Route::get('/program-bantuan/riwayat-saya/{id}', [ProgramBantuanController::class, 'show']);
    Route::post('/program-bantuan/riwayat-saya/{id}/absen-mandiri', [ProgramBantuanController::class, 'absenMandiri']);
    
    // Rumah Saya - Validasi & Update Data Rumah
    Route::get('/rumah-saya', [HouseController::class, 'getMyHouse']);
    Route::post('/rumah-saya/validate-nomor-rumah', [HouseController::class, 'validateNomorRumah']);
    Route::post('/rumah-saya/update', [HouseController::class, 'updateMyHouse']); // POST untuk support multipart/form-data dengan multiple foto
});

// Existing routes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/users-menu', [UsersMenuController::class, 'getMenus']);

Route::get('/users', [UsersController::class, 'apiIndex']);
