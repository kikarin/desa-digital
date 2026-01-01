<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\UsersMenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\CategoryPermissionController;
use App\Http\Controllers\RwsController;
use App\Http\Controllers\RtsController;
use App\Http\Controllers\HousesController;
use App\Http\Controllers\ResidentStatusController;
use App\Http\Controllers\KategoriAduanController;
use App\Http\Controllers\FamiliesController;
use App\Http\Controllers\ResidentsController;
use App\Http\Controllers\AssistanceProgramsController;
use App\Http\Controllers\AssistanceItemsController;
use App\Http\Controllers\AssistanceProgramItemsController;
use App\Http\Controllers\AssistanceRecipientsController;
use App\Http\Controllers\JenisSuratController;
use App\Http\Controllers\AtributJenisSuratController;
use App\Http\Controllers\PengajuanSuratController;
use App\Http\Controllers\AdminTandaTanganController;
use App\Http\Controllers\BeritaPengumumanController;
use App\Http\Controllers\BankSampahController;
use App\Http\Controllers\LayananDaruratController;
use App\Http\Controllers\AduanMasyarakatController;
use App\Http\Controllers\KategoriProposalController;
use App\Http\Controllers\PengajuanProposalController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Users Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/users', UsersController::class)->names('users');
    Route::get('/users/{id}/login-as', [UsersController::class, 'login_as'])->name('users.login-as');
    Route::post('/users/switch-role', [UsersController::class, 'switchRole'])->name('users.switch-role');
    Route::get('/api/users', [UsersController::class, 'apiIndex']);
    Route::post('/users/destroy-selected', [UsersController::class, 'destroy_selected'])->name('users.destroy_selected');
});

// Menus Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/menu-permissions/menus', UsersMenuController::class)
        ->names('menus');
    Route::get('/api/users-menu', [UsersMenuController::class, 'getMenus'])->name('api.users-menu');
    Route::get('/api/menus', [UsersMenuController::class, 'apiIndex'])->name('api.menus');
    Route::post('/menu-permissions/menus/destroy-selected', [UsersMenuController::class, 'destroy_selected'])->name('menus.destroy-selected');
});

// Roles Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/menu-permissions/roles', RoleController::class)->names('roles');
    Route::get('/api/roles', [RoleController::class, 'apiIndex']);
    Route::get('/menu-permissions/roles/set-permissions/{id}', [RoleController::class, 'set_permission'])->name('roles.set-permission');
    Route::post('/menu-permissions/roles/set-permissions/{id}', [RoleController::class, 'set_permission_action'])->name('roles.set-permission-action');
    Route::post('/menu-permissions/roles/destroy-selected', [RoleController::class, 'destroy_selected'])->name('roles.destroy_selected');
});

// Permissions & Category Permissions Routes
Route::middleware(['auth', 'verified'])->prefix('menu-permissions')->group(function () {
    Route::get('/permissions/create-permission', [PermissionController::class, 'create'])->name('permissions.create-permission');
    Route::post('/permissions/store-permission', [PermissionController::class, 'store'])->name('permissions.store-permission');
    Route::get('/permissions/{id}/edit-permission', [PermissionController::class, 'edit'])->name('permissions.edit-permission');
    Route::put('/permissions/update-permission/{id}', [PermissionController::class, 'update'])->name('permissions.update-permission');
    Route::get('/permissions/{id}/detail', [PermissionController::class, 'show'])->name('permissions.detail');
    Route::delete('/permissions/delete-permission/{id}', [PermissionController::class, 'destroy'])->name('permissions.delete-permission');

    // Kategori Permission (Category)
    Route::resource('/permissions', CategoryPermissionController::class)->names('permissions');
    // Custom: Show/Edit kategori by /category/{id}
    Route::get('/permissions/category/{id}', [CategoryPermissionController::class, 'show'])->name('permissions.category.show');
    Route::get('/permissions/category/{id}/edit', [CategoryPermissionController::class, 'edit'])->name('permissions.category.edit');
});

// Activity Logs Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/menu-permissions/logs', fn () => Inertia::render('modules/activity-logs/Index'))->name('access-control.logs.index');
    Route::get('/menu-permissions/logs/{id}', [ActivityLogController::class, 'show'])->name('access-control.logs.show');
    Route::get('/api/activity-logs', [ActivityLogController::class, 'apiIndex']);
    Route::delete('/menu-permissions/logs/{id}', [ActivityLogController::class, 'destroy'])->name('access-control.logs.destroy');
    Route::post('/menu-permissions/logs/destroy-selected', [ActivityLogController::class, 'destroy_selected'])->name('access-control.logs.destroy-selected');
});

// Rws Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/rws', RwsController::class)->names('rws');
    Route::get('/api/rws', [RwsController::class, 'apiIndex']);
    Route::post('/data-warga/rws/destroy-selected', [RwsController::class, 'destroy_selected'])->name('rws.destroy_selected');
    Route::post('/data-warga/rws/{id}/create-account', [RwsController::class, 'createAccount'])->name('rws.create-account');
});

// Rts Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/rts', RtsController::class)->names('rts');
    Route::get('/api/rts', [RtsController::class, 'apiIndex']);
    Route::post('/data-warga/rts/destroy-selected', [RtsController::class, 'destroy_selected'])->name('rts.destroy_selected');
    Route::post('/data-warga/rts/{id}/create-account', [RtsController::class, 'createAccount'])->name('rts.create-account');
});

// Houses Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/houses', HousesController::class)->names('houses');
    Route::get('/api/houses', [HousesController::class, 'apiIndex']);
    Route::get('/api/houses/{id}', [HousesController::class, 'apiShow']);
    Route::get('/api/houses-stats', [HousesController::class, 'apiStats']);
    Route::post('/data-warga/houses/destroy-selected', [HousesController::class, 'destroy_selected'])->name('houses.destroy_selected');
});

// Resident Status Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-master/resident-statuses', ResidentStatusController::class)->names('resident-statuses');
    Route::get('/api/resident-statuses', [ResidentStatusController::class, 'apiIndex']);
    Route::post('/data-master/resident-statuses/destroy-selected', [ResidentStatusController::class, 'destroy_selected'])->name('resident-statuses.destroy_selected');
});

// Kategori Aduan Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-master/kategori-aduan', KategoriAduanController::class)->names('kategori-aduan');
    Route::get('/api/kategori-aduan', [KategoriAduanController::class, 'apiIndex']);
    Route::post('/data-master/kategori-aduan/destroy-selected', [KategoriAduanController::class, 'destroy_selected'])->name('kategori-aduan.destroy_selected');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-master/kategori-proposal', KategoriProposalController::class)->names('kategori-proposal');
    Route::get('/api/kategori-proposal', [KategoriProposalController::class, 'apiIndex']);
    Route::post('/data-master/kategori-proposal/destroy-selected', [KategoriProposalController::class, 'destroy_selected'])->name('kategori-proposal.destroy_selected');
});

// Assistance Items Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-master/assistance-items', AssistanceItemsController::class)->names('assistance-items');
    Route::get('/api/assistance-items', [AssistanceItemsController::class, 'apiIndex']);
    Route::post('/data-master/assistance-items/destroy-selected', [AssistanceItemsController::class, 'destroy_selected'])->name('assistance-items.destroy_selected');
});

// Families Routes
Route::middleware(['auth', 'verified'])->group(function () {
        Route::resource('/data-warga/families', FamiliesController::class)->names('families');
        Route::get('/api/families', [FamiliesController::class, 'apiIndex']);
        Route::get('/api/families/{id}', [FamiliesController::class, 'apiShow']);
        Route::get('/api/families/{id}/residents', [FamiliesController::class, 'getResidents']);
        Route::put('/api/families/{id}/set-kepala-keluarga', [FamiliesController::class, 'setKepalaKeluarga']);
        Route::post('/data-warga/families/destroy-selected', [FamiliesController::class, 'destroy_selected'])->name('families.destroy_selected');
});

// Residents Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/residents', ResidentsController::class)->names('residents');
    Route::get('/api/residents', [ResidentsController::class, 'apiIndex']);
    Route::post('/data-warga/residents/destroy-selected', [ResidentsController::class, 'destroy_selected'])->name('residents.destroy_selected');
});

// Assistance Programs Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/program-bantuan/penyaluran', [AssistanceProgramsController::class, 'distribution'])->name('assistance-programs.distribution');
    Route::resource('/program-bantuan/program-bantuan', AssistanceProgramsController::class)->names('assistance-programs');
    Route::get('/api/assistance-programs', [AssistanceProgramsController::class, 'apiIndex']);
    Route::get('/api/assistance-recipients/distribution', [AssistanceProgramsController::class, 'apiDistribution']);
    Route::get('/api/assistance-recipients/{id}/family-residents', [AssistanceProgramsController::class, 'getFamilyResidents']);
    Route::put('/api/assistance-recipients/{id}/distribution', [AssistanceProgramsController::class, 'updateDistribution']);
    Route::post('/program-bantuan/program-bantuan/destroy-selected', [AssistanceProgramsController::class, 'destroy_selected'])->name('assistance-programs.destroy_selected');
});

// Assistance Program Items Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/program-bantuan/item-program', AssistanceProgramItemsController::class)->names('assistance-program-items');
    Route::get('/api/assistance-program-items', [AssistanceProgramItemsController::class, 'apiIndex']);
    Route::post('/program-bantuan/item-program/destroy-selected', [AssistanceProgramItemsController::class, 'destroy_selected'])->name('assistance-program-items.destroy_selected');
});

// Assistance Recipients Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Route create-multiple harus diletakkan SEBELUM resource route agar tidak konflik
    Route::get('/program-bantuan/penerima/create-multiple', [AssistanceRecipientsController::class, 'createMultiple'])->name('assistance-recipients.create-multiple');
    Route::post('/program-bantuan/penerima/store-multiple', [AssistanceRecipientsController::class, 'storeMultiple'])->name('assistance-recipients.store-multiple');
    Route::resource('/program-bantuan/penerima', AssistanceRecipientsController::class)->names('assistance-recipients');
    Route::get('/api/assistance-recipients', [AssistanceRecipientsController::class, 'apiIndex']);
    Route::get('/api/assistance-recipients/available-families', [AssistanceRecipientsController::class, 'getAvailableFamilies']);
    Route::get('/api/assistance-recipients/available-residents', [AssistanceRecipientsController::class, 'getAvailableResidents']);
    Route::post('/program-bantuan/penerima/destroy-selected', [AssistanceRecipientsController::class, 'destroy_selected'])->name('assistance-recipients.destroy_selected');
});

// Layanan Surat Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Jenis Surat Routes
    Route::resource('/layanan-surat/jenis-surat', JenisSuratController::class)->names('jenis-surat');
    Route::get('/api/jenis-surat', [JenisSuratController::class, 'apiIndex']);
    Route::get('/api/jenis-surat/{id}', [JenisSuratController::class, 'apiShow']);
    Route::post('/layanan-surat/jenis-surat/destroy-selected', [JenisSuratController::class, 'destroy_selected'])->name('jenis-surat.destroy_selected');

    // Atribut Jenis Surat Routes
    Route::resource('/layanan-surat/atribut-jenis-surat', AtributJenisSuratController::class)->names('atribut-jenis-surat');
    Route::get('/api/atribut-jenis-surat', [AtributJenisSuratController::class, 'apiIndex']);
    Route::post('/layanan-surat/atribut-jenis-surat/destroy-selected', [AtributJenisSuratController::class, 'destroy_selected'])->name('atribut-jenis-surat.destroy_selected');

    // Pengajuan Surat Routes
    Route::resource('/layanan-surat/pengajuan-surat', PengajuanSuratController::class)->names('pengajuan-surat');
    Route::put('/layanan-surat/pengajuan-surat/{id}', [PengajuanSuratController::class, 'update'])->name('pengajuan-surat.update');
    Route::get('/api/pengajuan-surat', [PengajuanSuratController::class, 'apiIndex']);
    Route::get('/api/pengajuan-saya', [PengajuanSuratController::class, 'apiIndexPengajuanSaya']);
    Route::get('/layanan-surat/pengajuan-surat/{id}/verifikasi', [PengajuanSuratController::class, 'verifikasi'])->name('pengajuan-surat.verifikasi');
    Route::post('/layanan-surat/pengajuan-surat/{id}/verifikasi', [PengajuanSuratController::class, 'storeVerifikasi'])->name('pengajuan-surat.store-verifikasi');
    Route::get('/layanan-surat/pengajuan-surat/{id}/preview-pdf', [PengajuanSuratController::class, 'previewPdf'])->name('pengajuan-surat.preview-pdf');
    Route::get('/layanan-surat/pengajuan-surat/{id}/export-pdf', [PengajuanSuratController::class, 'exportPdf'])->name('pengajuan-surat.export-pdf');
    Route::post('/layanan-surat/pengajuan-surat/destroy-selected', [PengajuanSuratController::class, 'destroy_selected'])->name('pengajuan-surat.destroy_selected');

    // Pengajuan Saya (untuk warga)
    Route::get('/layanan-surat/pengajuan-saya', [PengajuanSuratController::class, 'indexPengajuanSaya'])->name('pengajuan-saya.index');
    Route::get('/layanan-surat/pengajuan-saya/create', [PengajuanSuratController::class, 'createPengajuanSaya'])->name('pengajuan-saya.create');
    Route::post('/layanan-surat/pengajuan-saya', [PengajuanSuratController::class, 'store'])->name('pengajuan-saya.store');
    Route::get('/layanan-surat/pengajuan-saya/{id}', [PengajuanSuratController::class, 'showPengajuanSaya'])->name('pengajuan-saya.show');
    Route::get('/layanan-surat/pengajuan-saya/{id}/edit', [PengajuanSuratController::class, 'editPengajuanSaya'])->name('pengajuan-saya.edit');
    Route::put('/layanan-surat/pengajuan-saya/{id}', [PengajuanSuratController::class, 'update'])->name('pengajuan-saya.update');
    Route::post('/layanan-surat/pengajuan-saya/{id}', [PengajuanSuratController::class, 'update'])->name('pengajuan-saya.update-post'); // For method spoofing

    // Admin Tanda Tangan Routes
    Route::get('/api/admin-tanda-tangan', [AdminTandaTanganController::class, 'index'])->name('admin-tanda-tangan.index');
    Route::post('/api/admin-tanda-tangan', [AdminTandaTanganController::class, 'store'])->name('admin-tanda-tangan.store');
    Route::get('/api/admin-tanda-tangan/{type}', [AdminTandaTanganController::class, 'getByType'])->name('admin-tanda-tangan.get-by-type');
});

// Berita Pengumuman Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/berita-pengumuman', BeritaPengumumanController::class)->names('berita-pengumuman');
    Route::get('/api/berita-pengumuman', [BeritaPengumumanController::class, 'apiIndex']);
    Route::post('/berita-pengumuman/destroy-selected', [BeritaPengumumanController::class, 'destroy_selected'])->name('berita-pengumuman.destroy-selected');
});

// Bank Sampah Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/bank-sampah', BankSampahController::class)->names('bank-sampah');
    Route::get('/api/bank-sampah', [BankSampahController::class, 'apiIndex']);
    Route::get('/api/bank-sampah/{id}', [BankSampahController::class, 'apiShow']);
    Route::post('/bank-sampah/destroy-selected', [BankSampahController::class, 'destroy_selected'])->name('bank-sampah.destroy-selected');
});

// Layanan Darurat Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/layanan-darurat', LayananDaruratController::class)->names('layanan-darurat');
    Route::get('/api/layanan-darurat', [LayananDaruratController::class, 'apiIndex']);
    Route::get('/api/layanan-darurat/{id}', [LayananDaruratController::class, 'apiShow']);
    Route::post('/layanan-darurat/destroy-selected', [LayananDaruratController::class, 'destroy_selected'])->name('layanan-darurat.destroy-selected');
});

// Aduan Masyarakat Routes (Admin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/aduan-masyarakat', AduanMasyarakatController::class)->names('aduan-masyarakat');
    Route::get('/api/aduan-masyarakat', [AduanMasyarakatController::class, 'apiIndex']);
    Route::get('/api/aduan-masyarakat/{id}', [AduanMasyarakatController::class, 'apiShow']);
    Route::get('/aduan-masyarakat/{id}/verifikasi', [AduanMasyarakatController::class, 'verifikasi'])->name('aduan-masyarakat.verifikasi');
    Route::post('/aduan-masyarakat/{id}/verifikasi', [AduanMasyarakatController::class, 'storeVerifikasi'])->name('aduan-masyarakat.store-verifikasi');
    Route::post('/aduan-masyarakat/destroy-selected', [AduanMasyarakatController::class, 'destroy_selected'])->name('aduan-masyarakat.destroy-selected');
    Route::get('/api/kecamatan', [AduanMasyarakatController::class, 'getKecamatan']);
    Route::get('/api/desa/{kecamatanId}', [AduanMasyarakatController::class, 'getDesa']);
    Route::get('/api/aduan/kategori-aduan', [AduanMasyarakatController::class, 'getKategoriAduan']);
});

// Aduan Saya Routes (Warga)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/aduan-saya', [AduanMasyarakatController::class, 'indexSaya'])->name('aduan-saya.index');
    Route::get('/api/aduan-saya', [AduanMasyarakatController::class, 'apiIndexSaya']);
    Route::get('/aduan-saya/create', [AduanMasyarakatController::class, 'createSaya'])->name('aduan-saya.create');
    Route::post('/aduan-saya', [AduanMasyarakatController::class, 'store'])->name('aduan-saya.store');
    Route::get('/aduan-saya/{id}', [AduanMasyarakatController::class, 'showSaya'])->name('aduan-saya.show');
    Route::get('/aduan-saya/{id}/edit', [AduanMasyarakatController::class, 'editSaya'])->name('aduan-saya.edit');
    Route::put('/aduan-saya/{id}', [AduanMasyarakatController::class, 'update'])->name('aduan-saya.update');
    Route::delete('/aduan-saya/{id}', [AduanMasyarakatController::class, 'destroy'])->name('aduan-saya.destroy');
});

// Pengajuan Proposal Routes (Admin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/pengajuan-proposal', PengajuanProposalController::class)->names('pengajuan-proposal');
    Route::get('/api/pengajuan-proposal', [PengajuanProposalController::class, 'apiIndex']);
    Route::get('/api/pengajuan-proposal/{id}', [PengajuanProposalController::class, 'apiShow']);
    Route::post('/pengajuan-proposal/destroy-selected', [PengajuanProposalController::class, 'destroy_selected'])->name('pengajuan-proposal.destroy-selected');
    Route::get('/pengajuan-proposal/{id}/verifikasi', [PengajuanProposalController::class, 'verifikasi'])->name('pengajuan-proposal.verifikasi');
    Route::post('/pengajuan-proposal/{id}/verifikasi', [PengajuanProposalController::class, 'storeVerifikasi'])->name('pengajuan-proposal.store-verifikasi');
    Route::get('/pengajuan-proposal/{id}/preview-pdf', [PengajuanProposalController::class, 'previewPdf'])->name('pengajuan-proposal.preview-pdf');
    Route::get('/pengajuan-proposal/{id}/export-pdf', [PengajuanProposalController::class, 'exportPdf'])->name('pengajuan-proposal.export-pdf');
});

// Pengajuan Proposal Saya Routes (User)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pengajuan-proposal-saya', [PengajuanProposalController::class, 'indexPengajuanSaya'])->name('pengajuan-proposal-saya.index');
    Route::get('/pengajuan-proposal-saya/create', [PengajuanProposalController::class, 'createPengajuanSaya'])->name('pengajuan-proposal-saya.create');
    Route::post('/pengajuan-proposal-saya', [PengajuanProposalController::class, 'store'])->name('pengajuan-proposal-saya.store');
    Route::get('/pengajuan-proposal-saya/{id}', [PengajuanProposalController::class, 'showPengajuanSaya'])->name('pengajuan-proposal-saya.show');
    Route::get('/pengajuan-proposal-saya/{id}/edit', [PengajuanProposalController::class, 'editPengajuanSaya'])->name('pengajuan-proposal-saya.edit');
    Route::put('/pengajuan-proposal-saya/{id}', [PengajuanProposalController::class, 'update'])->name('pengajuan-proposal-saya.update');
    Route::get('/api/pengajuan-proposal-saya', [PengajuanProposalController::class, 'apiIndexPengajuanSaya']);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
