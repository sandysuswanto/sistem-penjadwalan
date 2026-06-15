<?php

use App\Http\Controllers\AngkatanController as ControllersAngkatanController;
use App\Http\Controllers\DosenController as ControllersDosenController;
use App\Http\Controllers\KelasController as ControllersKelasController;
use App\Http\Controllers\MataKuliahController as ControllersMataKuliahController;
use App\Http\Controllers\ProdiController as ControllersProdiController;
use App\Http\Controllers\RuanganController as ControllersRuanganController;
use App\Http\Controllers\UserController as ControllersUserController;
use App\Http\Controllers\SlotController as ControllersSlotController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\jadwalController;
use App\Http\Controllers\jadwalglobalController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WelcomeController;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::get('/schedule/list', [jadwalglobalController::class, 'listSchedule'])->name('jadwalglobal.list');
Route::get('/schedule/cetak', [jadwalglobalController::class, 'cetak'])->name('jadwalglobal.cetak');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
// API untuk cek kapasitas maksimal ruangan
Route::get('/get-max-ruangan-capacity', [ControllersKelasController::class, 'getMaxRuanganCapacity'])->name('getMaxRuanganCapacity');
Route::middleware(['auth'])->group(function () {
    Route::get('/schedule', [jadwalController::class, 'index'])->name('jadwalauth.index');
    Route::get('/jadwal/cetak', [jadwalController::class, 'cetak'])->name('jadwalauth.cetak');
    Route::get('/jadwal/analisis-slot', [jadwalController::class, 'showAnalisisSlot'])->name('jadwal.analisis-slot');
    Route::post('/matakuliah/cek-ketersediaan-ruangan', [ControllersMataKuliahController::class, 'cekKetersediaanRuangan']);
    Route::resource('matakuliah', ControllersMataKuliahController::class);
    Route::resource('dosen', ControllersDosenController::class);
    Route::get('/dosen/hari-statistik', [ControllersDosenController::class, 'getHariStatistik'])->name('dosen.hari.statistik');
});
Route::get('kaprodi/dashboardkaprodi', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:kaprodi'])
    ->name('dashboardkaprodi');
Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('dashboard');

// Route untuk Kaprodi
Route::middleware(['auth', 'role:kaprodi'])->group(function () {
    Route::resource('kelas', ControllersKelasController::class);

    // 🔥 ROUTE VALIDASI (pindah ke ProdiController)
    Route::post('/prodi/validate', [ControllersProdiController::class, 'validateData'])->name('prodi.validate');
    Route::post('/prodi/unvalidate', [ControllersProdiController::class, 'unvalidateData'])->name('prodi.unvalidate');
    Route::get('/prodi/{prodi}/edit', [ControllersProdiController::class, 'edit'])->name('prodi.edit');
    Route::get('/matakuliah/cek-sks-dosen', [ControllersMataKuliahController::class, 'cekSksDosen']);
    Route::get('/matakuliah/dosen-sks-statistik', [ControllersMataKuliahController::class, 'getDosenSksStatistik']);
});

// Route untuk Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('prodi', ControllersProdiController::class);
    Route::resource('ruangan', ControllersRuanganController::class);
    Route::resource('angkatan', ControllersAngkatanController::class);
    Route::resource('user', ControllersUserController::class);
    Route::resource('slot', ControllersSlotController::class);
    Route::post('/schedule/regenerate', [jadwalController::class, 'regenerate'])->name('jadwalauth.regenerate');
    Route::post('/jadwalauth/generate-ramadan', [jadwalController::class, 'generateRamadan'])->name('jadwalauth.generateRamadan');
    Route::get('/admin/audit', [AdminController::class, 'audit'])->name('admin.audit');
});
