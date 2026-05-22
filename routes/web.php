<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\LoanController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\FineController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Anggota\ProfileController;

use App\Http\Controllers\Admin\KelasController;


// ─── Public Routes (no auth) ───────────────────────────────────────────────
Route::get('/', [PublicController::class , 'home'])->name('home');
Route::get('/katalog', [PublicController::class , 'catalog'])->name('catalog');

// ─── Guest Routes ──────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class , 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class , 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class , 'register']);

    // Register OTP (WhatsApp)
    Route::post('/register/send-otp',   [AuthController::class, 'registerSendOtp'])->name('register.send-otp');
    Route::post('/register/verify-otp', [AuthController::class, 'registerVerifyOtp'])->name('register.verify-otp');

    // Lupa Password (OTP)
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->name('password.email');
    Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.verify');
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

    // Klaim Akun (NIS/NIP first-login)
    Route::get('/claim-account', [AuthController::class , 'showClaim'])->name('claim.show');
    Route::post('/claim-account/lookup', [AuthController::class , 'claimLookup'])->name('claim.lookup');
    Route::post('/claim-account/activate', [AuthController::class , 'claimActivate'])->name('claim.activate');

    // Claim Aktivasi OTP (Email)
    Route::post('/claim-account/send-otp',   [AuthController::class, 'claimActivateSendOtp'])->name('claim.send-otp');
    Route::post('/claim-account/verify-otp', [AuthController::class, 'claimActivateVerifyOtp'])->name('claim.verify-otp');
    Route::post('/claim-account/reset',      [AuthController::class, 'claimReset'])->name('claim.reset');
});

// ─── Auth Routes ──────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class , 'logout'])->name('logout');

    Route::middleware('role:anggota')->prefix('anggota')->name('anggota.')->group(function () {
        Route::get('/profile', [ProfileController::class , 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class , 'update'])->name('profile.update');
    });

        // ── Admin & Petugas ──────────────────────────────────────────────────
        Route::middleware('role:admin|petugas')->group(function () {

            // Dashboard
            Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

            // Members
            Route::get('/members/create', [MemberController::class , 'create'])->name('members.create');
            Route::post('/members', [MemberController::class , 'store'])->name('members.store');
            Route::post('/members/import', [MemberController::class , 'import'])->name('members.import');
            Route::get('/members/template/{type}', [MemberController::class , 'downloadTemplate'])->name('members.template');
            Route::get('/members', [MemberController::class , 'index'])->name('members.index');
            Route::get('/members/{member}', [MemberController::class , 'show'])->name('members.show');
            Route::post('/members/{member}/approve', [MemberController::class , 'approve'])->name('members.approve');
            Route::post('/members/{member}/reject', [MemberController::class , 'reject'])->name('members.reject');
            Route::post('/members/{member}/suspend', [MemberController::class , 'suspend'])->name('members.suspend');
            Route::post('/members/{member}/activate', [MemberController::class , 'activate'])->name('members.activate');
            Route::put('/members/{member}', [MemberController::class , 'update'])->name('members.update');
            Route::delete('/members/{member}', [MemberController::class , 'destroy'])->name('members.destroy');

            // Books
            Route::post('/books/import', [BookController::class , 'import'])->name('books.import');
            Route::get('/books/{book}/detail', [BookController::class , 'detail'])->name('books.detail');
            Route::get('/books/print-labels', [BookController::class , 'printLabels'])->name('books.print-labels');
            Route::resource('books', BookController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('/books/{book}/copies', [BookController::class , 'storeCopy'])->name('books.copies.store');
            Route::put('/copies/{copy}', [BookController::class , 'updateCopy'])->name('copies.update');

            // Transaksi & Riwayat
            Route::get('/peminjaman', fn() => \Inertia\Inertia::render('Admin/Loans/Index'))->name('loans.index');
            Route::get('/pengembalian', fn() => \Inertia\Inertia::render('Admin/Returns/Index'))->name('returns.index');
            Route::get('/riwayat', [LoanController::class , 'riwayat'])->name('history.index');
            
            // Presensi
            Route::get('/presensi', [VisitController::class, 'index'])->name('visits.index');
            Route::post('/presensi/check', [VisitController::class, 'check'])->name('visits.check');
            Route::post('/presensi', [VisitController::class, 'store'])->name('visits.store');
            Route::put('/presensi/{visit}', [VisitController::class, 'update'])->name('visits.update');
            Route::delete('/presensi/{visit}', [VisitController::class, 'destroy'])->name('visits.destroy');

            // Loans API
            Route::post('/loans', [LoanController::class , 'store'])->name('loans.store');
            Route::post('/loans/{loan}/extend', [LoanController::class , 'extend'])->name('loans.extend');

            // Scan helpers (JSON)
            Route::post('/loans/validate-member', [LoanController::class , 'validateMember'])->name('loans.validate-member');
            Route::post('/loans/validate-book', [LoanController::class , 'validateBook'])->name('loans.validate-book');

            // Returns API
            Route::post('/returns/check', [ReturnController::class , 'check'])->name('returns.check');
            Route::post('/returns', [ReturnController::class , 'store'])->name('returns.store');

            // Fines
            Route::get('/fines', [FineController::class , 'index'])->name('fines.index');
            Route::post('/fines/{fine}/pay', [FineController::class , 'pay'])->name('fines.pay');
            Route::post('/fines/{fine}/free', [FineController::class , 'free'])->name('fines.free');

            // Kelas
            Route::resource('kelas', KelasController::class)->only(['index', 'store', 'update', 'destroy']);

            // Laporan
            Route::get('/laporan/denda', [ReportController::class, 'fineReport'])->name('reports.fines');
            Route::get('/laporan/presensi', [ReportController::class, 'attendanceReport'])->name('reports.attendance');

        // Settings (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('/settings', [SettingController::class , 'index'])->name('settings.index');
            Route::post('/settings', [SettingController::class , 'update'])->name('settings.update');
        });

        // Notifications
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    });
});