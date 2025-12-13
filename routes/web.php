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
use App\Http\Controllers\FamiliesController;
use App\Http\Controllers\ResidentsController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
});

// Rts Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/rts', RtsController::class)->names('rts');
    Route::get('/api/rts', [RtsController::class, 'apiIndex']);
    Route::post('/data-warga/rts/destroy-selected', [RtsController::class, 'destroy_selected'])->name('rts.destroy_selected');
});

// Houses Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-warga/houses', HousesController::class)->names('houses');
    Route::get('/api/houses', [HousesController::class, 'apiIndex']);
    Route::post('/data-warga/houses/destroy-selected', [HousesController::class, 'destroy_selected'])->name('houses.destroy_selected');
});

// Resident Status Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/data-master/resident-statuses', ResidentStatusController::class)->names('resident-statuses');
    Route::get('/api/resident-statuses', [ResidentStatusController::class, 'apiIndex']);
    Route::post('/data-master/resident-statuses/destroy-selected', [ResidentStatusController::class, 'destroy_selected'])->name('resident-statuses.destroy_selected');
});

// Families Routes
Route::middleware(['auth', 'verified'])->group(function () {
        Route::resource('/data-warga/families', FamiliesController::class)->names('families');
        Route::get('/api/families', [FamiliesController::class, 'apiIndex']);
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

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
