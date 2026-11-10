<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Panel\ActivityLogController;
use App\Http\Controllers\Panel\ArchiveController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\IncomingLetterController;
use App\Http\Controllers\Panel\OutgoingLetterController;
use App\Http\Controllers\Panel\SignatureController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// ---- Publik (tanpa login) ----
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/ajukan', [PublicController::class, 'create'])->name('public.create');
Route::post('/ajukan', [PublicController::class, 'store'])->middleware('throttle:10,1')->name('public.store');
Route::get('/lacak', [TrackingController::class, 'index'])->middleware('throttle:60,1')->name('track');
Route::get('/lacak/unduh/{letter}', [TrackingController::class, 'download'])->middleware('signed')->name('track.download');

// ---- Autentikasi ----
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:8,1');
});
Route::post('/keluar', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---- Panel (admin & kaprodi) ----
$staff = EnsureRole::class.':admin,kaprodi';
$admin = EnsureRole::class.':admin';
$kaprodi = EnsureRole::class.':kaprodi';

Route::prefix('panel')->name('panel.')->middleware(['auth', $staff])->group(function () use ($admin, $kaprodi) {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Surat keluar
    Route::get('/surat-keluar', [OutgoingLetterController::class, 'index'])->name('outgoing.index');
    Route::get('/surat-keluar/{letter}', [OutgoingLetterController::class, 'show'])->name('outgoing.show');
    Route::get('/surat-keluar/{letter}/pratinjau', [OutgoingLetterController::class, 'preview'])->name('outgoing.preview');
    Route::get('/surat-keluar/{letter}/docx', [OutgoingLetterController::class, 'docx'])->name('outgoing.docx');
    Route::get('/surat-keluar/{letter}/berkas/{kind}', [OutgoingLetterController::class, 'download'])->name('outgoing.download');
    Route::post('/surat-keluar/{letter}/tolak', [OutgoingLetterController::class, 'reject'])->name('outgoing.reject');
    // approve & revise: dicek lagi lewat User::canApprove() (kaprodi, atau admin bila diizinkan di config)
    Route::post('/surat-keluar/{letter}/acc', [OutgoingLetterController::class, 'approve'])->name('outgoing.approve');
    Route::post('/surat-keluar/{letter}/revisi', [OutgoingLetterController::class, 'revise'])->name('outgoing.revise');

    Route::middleware($admin)->group(function () {
        Route::post('/surat-keluar/{letter}/proses', [OutgoingLetterController::class, 'start'])->name('outgoing.start');
        Route::post('/surat-keluar/{letter}/template', [OutgoingLetterController::class, 'saveTemplate'])->name('outgoing.template');
        Route::post('/surat-keluar/{letter}/draf', [OutgoingLetterController::class, 'uploadDraft'])->name('outgoing.draft');
        Route::post('/surat-keluar/{letter}/ajukan', [OutgoingLetterController::class, 'submit'])->name('outgoing.submit');

        Route::get('/surat-masuk/baru', [IncomingLetterController::class, 'create'])->name('incoming.create');
        Route::post('/surat-masuk', [IncomingLetterController::class, 'store'])->name('incoming.store');
        Route::post('/surat-masuk/{incoming}/selesai', [IncomingLetterController::class, 'finish'])->name('incoming.finish');
    });

    // Surat masuk
    Route::get('/surat-masuk', [IncomingLetterController::class, 'index'])->name('incoming.index');
    Route::get('/surat-masuk/{incoming}', [IncomingLetterController::class, 'show'])->name('incoming.show');
    Route::get('/surat-masuk/{incoming}/berkas', [IncomingLetterController::class, 'download'])->name('incoming.download');
    Route::post('/disposisi/{disposition}/selesai', [IncomingLetterController::class, 'completeDisposition'])->name('disposition.complete');
    Route::post('/surat-masuk/{incoming}/disposisi', [IncomingLetterController::class, 'dispose'])->middleware($kaprodi)->name('incoming.dispose');

    // Arsip, laporan, log
    Route::get('/arsip', [ArchiveController::class, 'index'])->name('archive.index');
    Route::get('/arsip/cetak', [ArchiveController::class, 'print'])->name('archive.print');
    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('logs.index');

    // Profil & TTD
    Route::get('/profil', [SignatureController::class, 'edit'])->name('profile');
    Route::put('/profil', [SignatureController::class, 'update'])->name('profile.update');
    Route::get('/profil/ttd', [SignatureController::class, 'preview'])->name('profile.signature');
});
