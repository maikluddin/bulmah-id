<?php

use App\Http\Controllers\Admin\AbsensiController as AdminAbsensiController;
use App\Http\Controllers\Admin\HafalanController as AdminHafalanController;
use App\Http\Controllers\Admin\HafalanKategoriController as AdminHafalanKategoriController;
use App\Http\Controllers\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Admin\KeuanganController as AdminKeuanganController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\LembagaController as AdminLembagaController;
use App\Http\Controllers\Admin\MonitoringController as AdminMonitoringController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Admin\PendaftaranController as AdminPendaftaranController;
use App\Http\Controllers\Admin\PerbaikanSarprasController as AdminPerbaikanSarprasController;
use App\Http\Controllers\Admin\PerizinanController as AdminPerizinanController;
use App\Http\Controllers\Admin\PostCategoryController as AdminPostCategoryController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\SantriController as AdminSantriController;
use App\Http\Controllers\Admin\SarprasController as AdminSarprasController;
use App\Http\Controllers\Admin\SetoranController as AdminSetoranController;
use App\Http\Controllers\Admin\WaliController as AdminWaliController;
use App\Http\Controllers\Admin\WalletController as AdminWalletController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Wali\WaliController;
use App\Http\Controllers\Website\AboutController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\LembagaController;
use App\Http\Controllers\Website\NewsController;
use App\Http\Controllers\Website\PendaftaranController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('berita', [NewsController::class, 'index'])->name('news.index');
Route::get('berita/{post}', [NewsController::class, 'show'])->where('post', '[a-z0-9-]+')->name('news.show');
Route::get('lembaga', [LembagaController::class, 'index'])->name('lembaga.index');
Route::get('lembaga/{lembaga:slug}', [LembagaController::class, 'show'])->name('lembaga.show');
Route::get('lembaga/{lembaga:slug}/daftar', [PendaftaranController::class, 'create'])->name('daftar.create');
Route::post('lembaga/{lembaga:slug}/daftar', [PendaftaranController::class, 'store'])->name('daftar.store');
Route::get('profil', AboutController::class)->name('about');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('berita', AdminPostController::class)
            ->except('show')
            ->parameters(['berita' => 'post'])
            ->names('admin.posts');

        Route::resource('kategori', AdminPostCategoryController::class)
            ->except(['create', 'show', 'edit'])
            ->parameters(['kategori' => 'category'])
            ->names('admin.categories');

        Route::resource('santri', AdminSantriController::class)
            ->except('show')
            ->names('admin.santri');

        Route::get('kelas', [AdminKelasController::class, 'index'])->name('admin.kelas.index');
        Route::post('kelas', [AdminKelasController::class, 'store'])->name('admin.kelas.store');
        Route::put('kelas/{kelas}', [AdminKelasController::class, 'update'])->name('admin.kelas.update');
        Route::delete('kelas/{kelas}', [AdminKelasController::class, 'destroy'])->name('admin.kelas.destroy');

        Route::get('kategori-hafalan', [AdminHafalanKategoriController::class, 'index'])->name('admin.hafalan-kategori.index');
        Route::post('kategori-hafalan', [AdminHafalanKategoriController::class, 'store'])->name('admin.hafalan-kategori.store');
        Route::post('kategori-hafalan/template', [AdminHafalanKategoriController::class, 'storeTemplate'])->name('admin.hafalan-kategori.template');
        Route::put('kategori-hafalan/{kategori}', [AdminHafalanKategoriController::class, 'update'])->name('admin.hafalan-kategori.update');
        Route::delete('kategori-hafalan/{kategori}', [AdminHafalanKategoriController::class, 'destroy'])->name('admin.hafalan-kategori.destroy');

        Route::get('santri/{santri}/monitoring', [AdminMonitoringController::class, 'show'])->name('admin.santri.monitoring');

        Route::get('pendaftaran', [AdminPendaftaranController::class, 'index'])->name('admin.pendaftaran.index');
        Route::post('pendaftaran/{santri}/terima', [AdminPendaftaranController::class, 'accept'])->name('admin.pendaftaran.accept');
        Route::post('pendaftaran/{santri}/tolak', [AdminPendaftaranController::class, 'reject'])->name('admin.pendaftaran.reject');

        Route::get('santri/{santri}/hafalan', [AdminHafalanController::class, 'index'])->name('admin.santri.hafalan.index');
        Route::post('santri/{santri}/hafalan', [AdminHafalanController::class, 'store'])->name('admin.santri.hafalan.store');
        Route::delete('santri/{santri}/hafalan/{hafalan}', [AdminHafalanController::class, 'destroy'])->name('admin.santri.hafalan.destroy');

        Route::get('santri/{santri}/absensi', [AdminAbsensiController::class, 'index'])->name('admin.santri.absensi.index');
        Route::post('santri/{santri}/absensi', [AdminAbsensiController::class, 'store'])->name('admin.santri.absensi.store');
        Route::delete('santri/{santri}/absensi/{absensi}', [AdminAbsensiController::class, 'destroy'])->name('admin.santri.absensi.destroy');

        Route::get('santri/{santri}/wallet', [AdminWalletController::class, 'index'])->name('admin.santri.wallet.index');
        Route::post('santri/{santri}/wallet', [AdminWalletController::class, 'store'])->name('admin.santri.wallet.store');
        Route::delete('santri/{santri}/wallet/{transaction}', [AdminWalletController::class, 'destroy'])->name('admin.santri.wallet.destroy');

        Route::get('santri/{santri}/pembayaran', [AdminPembayaranController::class, 'index'])->name('admin.santri.pembayaran.index');
        Route::post('santri/{santri}/pembayaran', [AdminPembayaranController::class, 'store'])->name('admin.santri.pembayaran.store');
        Route::delete('santri/{santri}/pembayaran/{pembayaran}', [AdminPembayaranController::class, 'destroy'])->name('admin.santri.pembayaran.destroy');

        Route::get('santri/{santri}/perizinan', [AdminPerizinanController::class, 'index'])->name('admin.santri.perizinan.index');
        Route::post('santri/{santri}/perizinan', [AdminPerizinanController::class, 'store'])->name('admin.santri.perizinan.store');
        Route::patch('santri/{santri}/perizinan/{perizinan}', [AdminPerizinanController::class, 'updateStatus'])->name('admin.santri.perizinan.update');
        Route::delete('santri/{santri}/perizinan/{perizinan}', [AdminPerizinanController::class, 'destroy'])->name('admin.santri.perizinan.destroy');

        Route::get('setoran', [AdminSetoranController::class, 'index'])->name('admin.setoran.index');
        Route::post('setoran', [AdminSetoranController::class, 'store'])->name('admin.setoran.store');

        Route::get('wali', [WaliController::class, 'index'])->name('wali.index');

        Route::get('akun-wali', [AdminWaliController::class, 'index'])->name('admin.wali.index');
        Route::get('akun-wali/create', [AdminWaliController::class, 'create'])->name('admin.wali.create');
        Route::post('akun-wali', [AdminWaliController::class, 'store'])->name('admin.wali.store');
        Route::get('akun-wali/{user}/edit', [AdminWaliController::class, 'edit'])->name('admin.wali.edit');
        Route::put('akun-wali/{user}', [AdminWaliController::class, 'update'])->name('admin.wali.update');
        Route::delete('akun-wali/{user}', [AdminWaliController::class, 'destroy'])->name('admin.wali.destroy');

        Route::get('keuangan', [AdminKeuanganController::class, 'index'])->name('admin.keuangan.index');

        Route::get('sarpras', [AdminSarprasController::class, 'index'])->name('admin.sarpras.index');
        Route::post('sarpras', [AdminSarprasController::class, 'store'])->name('admin.sarpras.store');
        Route::put('sarpras/{sarpras_item}', [AdminSarprasController::class, 'update'])->name('admin.sarpras.update');
        Route::delete('sarpras/{sarpras_item}', [AdminSarprasController::class, 'destroy'])->name('admin.sarpras.destroy');
        Route::post('sarpras/perbaikan', [AdminPerbaikanSarprasController::class, 'store'])->name('admin.sarpras.perbaikan.store');
        Route::patch('sarpras/perbaikan/{perbaikan}', [AdminPerbaikanSarprasController::class, 'updateStatus'])->name('admin.sarpras.perbaikan.update');
        Route::delete('sarpras/perbaikan/{perbaikan}', [AdminPerbaikanSarprasController::class, 'destroy'])->name('admin.sarpras.perbaikan.destroy');

        Route::get('laporan/setoran', [AdminLaporanController::class, 'setoran'])->name('admin.laporan.setoran');

        Route::get('lembaga', [AdminLembagaController::class, 'index'])->name('admin.lembaga.index');
        Route::get('lembaga/profil', [AdminLembagaController::class, 'edit'])->name('admin.lembaga.edit');
        Route::put('lembaga', [AdminLembagaController::class, 'update'])->name('admin.lembaga.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
