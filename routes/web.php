<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\DeviceController;
use App\Http\Controllers\Client\HistoryController;
use App\Http\Controllers\Client\HotspotTemplateController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\RouterController;
use App\Http\Controllers\Client\UserController;
use App\Http\Controllers\Hotspot\TemplateFileController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// --- Hotspot template files, downloaded by MikroTik /tool fetch ---
Route::get('/hs-template/{token}/{file}', TemplateFileController::class)
    ->where(['token' => '[A-Za-z0-9]{40}', 'file' => '[a-z0-9]+\.[a-z0-9]+'])
    ->middleware('throttle:240,1')
    ->name('hotspot-template.file');

// --- Client auth (default login) ---
Route::get('/login', [ClientLoginController::class, 'create'])->name('login');
Route::post('/login', [ClientLoginController::class, 'store'])->middleware('throttle:20,1')->name('login.attempt');
Route::post('/logout', [ClientLoginController::class, 'destroy'])->name('logout');

// --- Admin auth ---
Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:20,1')->name('admin.login.attempt');
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');

// --- Admin panel ---
Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::resource('clients', AdminClientController::class)->except(['destroy']);
    Route::post('/clients/{client}/impersonate', [AdminClientController::class, 'impersonate'])->name('clients.impersonate');
    Route::post('/clients/{client}/users/{clientUser}/reset-password', [AdminClientController::class, 'resetUserPassword'])
        ->name('clients.users.reset-password');
});

// --- Client panel ---
Route::middleware('auth:client')->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', ClientDashboardController::class)->name('dashboard');

    Route::resource('profiles', ProfileController::class)->except(['show']);

    Route::get('/routers/live-status', [RouterController::class, 'liveStatus'])->name('routers.live-status');
    Route::resource('routers', RouterController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/routers/{router}/connect', [RouterController::class, 'connect'])->name('routers.connect');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/batch', [UserController::class, 'storeBatch'])->name('users.batch');
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::get('/users/{hotspotUser}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{hotspotUser}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{hotspotUser}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{hotspotUser}/sessions', [UserController::class, 'sessions'])->name('users.sessions');
    Route::post('/users/{hotspotUser}/sessions/{radacctId}/kill', [UserController::class, 'killSession'])->name('users.sessions.kill');
    Route::post('/users/{hotspotUser}/bypass/{router}/kill', [UserController::class, 'killBypass'])->name('users.bypass.kill');

    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');

    Route::get('/hotspot-template', [HotspotTemplateController::class, 'edit'])->name('hotspot-template.edit');
    Route::put('/hotspot-template', [HotspotTemplateController::class, 'update'])->name('hotspot-template.update');
    Route::get('/hotspot-template/storage', [HotspotTemplateController::class, 'storage'])->name('hotspot-template.storage');
    Route::get('/hotspot-template/preview/{file}', [HotspotTemplateController::class, 'preview'])
        ->where('file', '[a-z0-9.]+')->name('hotspot-template.preview');
    Route::post('/hotspot-template/apply/{router}', [HotspotTemplateController::class, 'apply'])->name('hotspot-template.apply');
    Route::post('/hotspot-template/restore/{router}', [HotspotTemplateController::class, 'restore'])->name('hotspot-template.restore');

    Route::view('/help', 'client.help.index')->name('help');

    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');
});
