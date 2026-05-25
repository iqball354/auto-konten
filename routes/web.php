<?php

use App\Http\Controllers\akun_terhubungController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboardcontroller;
use App\Http\Controllers\ekspor_dataController;
use App\Http\Controllers\kelola_usereController;
use App\Http\Controllers\postinganController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CaptionController;
use App\Http\Controllers\AiRecommendationController;

/*
|--------------------------------------------------------------------------
| Web Routes — WAIG Pilot v1.0
|--------------------------------------------------------------------------
*/

// ============================================================
// PUBLIC
// ============================================================

Route::get('/', function () { return view('welcome'); });

Route::get('/users', function () {
    return response()->json(['nama' => 'Iqbal', 'status' => 'API Laravel berhasil']);
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

// ============================================================
// GUEST ONLY
// ============================================================

Route::middleware('guest')->group(function () {

    Route::get('/login',    [AuthController::class, 'ShowLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'Login']);

    Route::get('/register', [AuthController::class, 'ShowRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'Register']);

    // Reset Password ← BARU
    Route::get('/forgot-password',        [AuthController::class, 'ShowForgotPassword'])->name('password.request');
    Route::post('/forgot-password',       [AuthController::class, 'SendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'ShowResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'ResetPassword'])->name('password.update');
});

// ============================================================
// AUTHENTICATED
// ============================================================

Route::middleware(['auth', 'active'])->group(function () {

    // Logout (lama)
    Route::get('/logout',  [AuthController::class, 'Logout'])->name('logout');
    Route::post('/logout', [AuthController::class, 'Logout']);

    // Dashboard (lama)
    Route::get('/dashboard', [Dashboardcontroller::class, 'ShowDashboard'])->name('dashboard');

    // Notifikasi ← BARU (ditaruh di Dashboardcontroller sesuai pola yang ada)
    Route::get('/notifikasi',              [Dashboardcontroller::class, 'ShowNotifikasi'])->name('notifikasi');
    Route::post('/notifikasi/{id}/baca',   [Dashboardcontroller::class, 'TandaiBaca'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua',  [Dashboardcontroller::class, 'TandaiBacaSemua'])->name('notifikasi.baca-semua');

    // Orders ← BARU
    Route::get('/payment/qris', [PaymentController::class, 'showQris'])->name('payment.qris');
    Route::post('/payment/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
    Route::post('/payment/upload-bukti/{orderId}', [PaymentController::class, 'uploadBukti'])->name('payment.upload-bukti');
    Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    Route::get('/payment/latest-status', [PaymentController::class, 'latestStatus'])->name('payment.latest-status');
    
    //admin only
    Route::get('/admin/payment', [PaymentController::class, 'adminIndex'])->name('admin.payment');
    Route::post('/admin/payment/{orderId}/konfirmasi',[PaymentController::class, 'konfirmasi'])->name('payment.konfirmasi');
    Route::post('/admin/payment/{orderId}/tolak', [PaymentController::class, 'tolak'])->name('payment.tolak');

    // ----------------------------------------------------------
    // Akun Terhubung
    // ----------------------------------------------------------

    Route::get('/akun_terhubung',           [akun_terhubungController::class, 'Showakun_terhubung'])->name('akun_terhubung');           // lama
    Route::post('/akun_terhubung/tambah',   [akun_terhubungController::class, 'Tambah'])->name('akun_terhubung.tambah');                // lama
    Route::get('/meta/redirect',            [akun_terhubungController::class, 'facebookRedirect'])->name('meta.redirect');              // oauth redirect
    Route::get('/meta/callback',            [akun_terhubungController::class, 'facebookCallback'])->name('meta.callback');              // oauth callback
    Route::post('/meta/save-page',          [akun_terhubungController::class, 'SaveFacebookPage'])->name('meta.save-page');             // simpan page terpilih
    Route::delete('/akun_terhubung/{id}',   [akun_terhubungController::class, 'Hapus'])->name('akun_terhubung.hapus');                  // ← BARU: putuskan koneksi akun
    Route::get('/akun_terhubung/{id}/status',[akun_terhubungController::class, 'CekStatus'])->name('akun_terhubung.status');           // ← BARU: cek status & validitas token

    // ----------------------------------------------------------
    // Postingan
    // ----------------------------------------------------------

    Route::get('/postingan',               [postinganController::class, 'ShowPosting'])->name('postingan');                             // lama
    Route::post('/postingan',              [postinganController::class, 'StorePosting'])->name('posting.store');                        // lama                         // ← BARU: detail konten
    Route::get('/postingan/{id}/edit',     [postinganController::class, 'ShowEditPosting'])->name('postingan.edit');                    // ← BARU: form edit konten
    Route::put('/postingan/{id}',          [postinganController::class, 'UpdatePosting'])->name('postingan.update');                    // ← BARU: edit konten (draft/scheduled)
    Route::delete('/postingan/{id}',       [postinganController::class, 'HapusPosting'])->name('postingan.hapus');                      // ← BARU: soft delete
    Route::post('/postingan/{id}/media',             [postinganController::class, 'UploadMedia'])->name('postingan.media.upload');      // ← BARU: upload media
    Route::delete('/postingan/{id}/media/{mediaId}', [postinganController::class, 'HapusMedia'])->name('postingan.media.hapus');        // ← BARU: hapus media
    Route::post('/postingan/{id}/publish-now',       [postinganController::class, 'PublishNow'])->name('postingan.publish-now');        // ← BARU: publish langsung
    Route::get('/post/{postId}/info', [postinganController::class, 'getPostInfo'])->name('post.info');
    Route::post('/captions/generate-ajax', [CaptionController::class, 'generateAjax'])->name('captions.generate.ajax');

    // ----------------------------------------------------------
    // Caption Generator    ← BARU
    // ----------------------------------------------------------

    Route::get('/captions', [CaptionController::class, 'index'])->name('captions.index');
    Route::post('/captions/generate', [CaptionController::class, 'generate'])->name('captions.generate');
    Route::delete('/captions/{caption}', [CaptionController::class, 'destroy'])->name('captions.destroy');
    
    // ----------------------------------------------------------
    // AI Recommendation — Best Posting Time ← BARU
    // ----------------------------------------------------------

    Route::get('/ai/recommendation', [AiRecommendationController::class, 'index'])->name('ai.recommendation');
    Route::get('/ai/recommendation/data', [AiRecommendationController::class, 'getRecommendation'])->name('ai.recommendation.data');
    Route::get('/ai/recommendation/chart', [AiRecommendationController::class, 'getChartData'])->name('ai.recommendation.chart');

    // ----------------------------------------------------------
    // Jadwal Posting ← BARU semua
    // ----------------------------------------------------------

    Route::get('/jadwal',          [postinganController::class, 'ShowJadwal'])->name('jadwal');                   // daftar jadwal
    Route::post('/jadwal',         [postinganController::class, 'StoreJadwal'])->name('jadwal.store');            // buat jadwal baru
    Route::put('/jadwal/{id}',     [postinganController::class, 'UpdateJadwal'])->name('jadwal.update');          // edit jadwal (pending)
    Route::delete('/jadwal/{id}',  [postinganController::class, 'BatalJadwal'])->name('jadwal.batal');            // batalkan jadwal
    Route::get('/jadwal/kalender', [postinganController::class, 'ShowKalender'])->name('jadwal.kalender');        // tampilan kalender

    // ----------------------------------------------------------
    // Riwayat & Log Posting ← BARU semua
    // ----------------------------------------------------------

    Route::get('/riwayat',       [postinganController::class, 'ShowRiwayat'])->name('riwayat');                   // riwayat posting
    Route::get('/riwayat/{id}',  [postinganController::class, 'ShowLogDetail'])->name('riwayat.show');            // detail log + error message

    // ----------------------------------------------------------
    // Ekspor Data
    // ----------------------------------------------------------

    Route::get('/ekspor_data',        [ekspor_dataController::class, 'ShowExport'])->name('ekspor_data');         // lama
    Route::post('/ekspor_data/excel', [ekspor_dataController::class, 'ExportExcel'])->name('ekspor_data.excel'); // ← BARU: export Excel
    Route::post('/ekspor_data/pdf',   [ekspor_dataController::class, 'ExportPdf'])->name('ekspor_data.pdf');     // ← BARU: export PDF

    // ----------------------------------------------------------
    // Profile
    // ----------------------------------------------------------

    Route::get('/profile',           [profileController::class, 'ShowProfile'])->name('profile');                 // lama
    Route::post('/profile/update',   [profileController::class, 'UpdateProfile'])->name('profile.update');        // lama
    Route::post('/profile/password', [profileController::class, 'UpdatePassword'])->name('profile.password');     // ← BARU: ganti password

    // ----------------------------------------------------------
    // Kelola User & Admin ← middleware 'admin' BARU
    // ----------------------------------------------------------

    // Route lama (tanpa middleware admin — sesuai kondisi awal project)
    Route::get('/kelola_user',        [kelola_usereController::class, 'ShowUser'])->name('kelola_user');
    Route::get('/kelola_user/{id}',   [kelola_usereController::class, 'UpdateUser'])->name('kelola_user.update');
    Route::post('/kelola_user/{id}',  [kelola_usereController::class, 'UpdateUser']);
    Route::post('/kelola_user/{id}/verify-gmail', [kelola_usereController::class, 'VerifyGmail'])->name('kelola_user.verify-gmail');
    Route::delete('/kelola_user/social-account/{id}', [kelola_usereController::class, 'DeleteSocialAccount'])->name('kelola_user.social-account.delete');

    // Route admin baru — uncomment baris 'middleware admin' di bawah
    // jika AdminMiddleware sudah dibuat & didaftarkan di Kernel.php
    // Route::middleware('admin')->group(function () {

        Route::post('/kelola_user',                   [kelola_usereController::class, 'TambahUser'])->name('kelola_user.store');              // ← BARU: tambah user
        Route::put('/kelola_user/{id}/toggle-active', [kelola_usereController::class, 'ToggleActive'])->name('kelola_user.toggle-active');    // ← BARU: aktif/nonaktif
        Route::post('/kelola_user/{id}/reset-password',[kelola_usereController::class, 'ResetPassword'])->name('kelola_user.reset-password'); // ← BARU: reset password
        Route::put('/kelola_user/{id}/api-key',       [kelola_usereController::class, 'SetApiKey'])->name('kelola_user.api-key');             // ← BARU: set Meta API key

        // Subscription
        Route::get('/subscription',       [kelola_usereController::class, 'ShowSubscription'])->name('subscription');                         // ← BARU: daftar subscription
        Route::put('/subscription/{id}',  [kelola_usereController::class, 'UpdateSubscription'])->name('subscription.update');                // ← BARU: update subscription

        // Log Admin
        Route::get('/admin/log',      [ekspor_dataController::class, 'ShowAdminLog'])->name('admin.log');                                     // ← BARU: semua log sistem
        Route::get('/admin/log/{id}', [ekspor_dataController::class, 'ShowLogDetail'])->name('admin.log.show');                               // ← BARU: detail log
        Route::get('/admin/statistik',[Dashboardcontroller::class, 'ShowStatistik'])->name('admin.statistik');                                // ← BARU: statistik platform

    // }); // end middleware admin

}); // end auth