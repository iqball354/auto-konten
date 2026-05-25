<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AkunTerhubungService;
use App\Services\MetaService;
use App\Services\FacebookOAuthService;
use Illuminate\Support\Facades\Log;
use Throwable;

class akun_terhubungController extends Controller
{
    public function __construct(
        private readonly AkunTerhubungService $akunTerhubungService,
        private readonly MetaService $meta,
        private readonly FacebookOAuthService $oauthService
    ) {
    }

    // ================================================================
    // Showakun_terhubung
    // GET /akun_terhubung
    // ================================================================

    public function Showakun_terhubung()
    {
        try {
            $sosial_accounts = $this->akunTerhubungService->getAccountsForUser((int) auth()->id());

            $pendingFacebookPages = session('pending_facebook_pages', []);
            return view('akun_terhubung', compact('sosial_accounts', 'pendingFacebookPages'));
        } catch (Throwable $e) {
            Log::error('Showakun_terhubung gagal.', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect('/dashboard')->with('error', 'Gagal memuat halaman akun terhubung.');
        }
    }

    // ================================================================
    // Tambah
    // POST /akun_terhubung/tambah
    // ================================================================

    public function Tambah(Request $request)
    {
        $request->validate([
            'platform'         => 'required|in:instagram,facebook',
            'platform_user_id' => 'required|string|max:255',
            'username'         => 'nullable|string|max:255',
            'access_token'     => 'required|string',
        ]);

        try {
            $this->akunTerhubungService->addAccount((int) auth()->id(), [
                'platform'         => (string) $request->platform,
                'platform_user_id' => (string) $request->platform_user_id,
                'username'         => $request->username ? (string) $request->username : null,
                'access_token'     => (string) $request->access_token,
            ]);

            return redirect('/akun_terhubung')
                ->with('success', 'Akun berhasil ditambahkan.');
        } catch (Throwable $e) {

            return redirect('/akun_terhubung')
                ->with('error', $e->getMessage());
        }
    }

    // ================================================================
    // facebookRedirect
    // GET /meta.redirect
    // ================================================================

    public function facebookRedirect(Request $request)
    {
        try {
            return redirect($this->meta->getLoginUrl(false, null, null, true));
        } catch (Throwable $e) {
            Log::error('facebookRedirect gagal.', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect('/akun_terhubung')
                ->with('error', 'Gagal memulai login Facebook.');
        }
    }

    // ================================================================
    // facebookCallback
    // GET /meta.callback
    // ================================================================

    public function facebookCallback(Request $request)
    {
        if ($request->error_reason === 'user_denied' || $request->error === 'access_denied') {
            return redirect('/akun_terhubung')
                ->with('error', 'Izin akses dibatalkan oleh pengguna.');
        }

        if ($request->error_reason === 'invalid_scope' || str_contains((string) $request->error_description, 'Invalid Scopes')) {
            return redirect('/akun_terhubung')
                ->with('error', 'Izin Meta yang dibutuhkan untuk publish belum aktif di app. Pastikan scope publish sudah disetujui di Meta App Review.');
        }

        if (!$request->code) {
            return redirect('/akun_terhubung')
                ->with('error', 'Authorization code tidak ditemukan.');
        }

        try {
            $result = $this->oauthService->facebookCallback(
                (int) auth()->id(),
                (string) $request->code,
                $request->state
            );

            session([
                'pending_facebook_pages' => $result['pages'] ?? [],
                'meta_oauth_state' => $result['state'] ?? $request->state,
            ]);

            return redirect('/akun_terhubung')
                ->with('success', 'OAuth Facebook berhasil. Silakan pilih Facebook Page yang ingin dihubungkan.');

        } catch (Throwable $e) {
            Log::error('facebookCallback gagal.', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect('/akun_terhubung')
                ->with('error', 'Gagal menghubungkan akun: ' . $e->getMessage());
        }
    }

    // ================================================================
    // SaveFacebookPage
    // POST /meta.save-page
    // ================================================================

    public function SaveFacebookPage(Request $request)
    {
        $request->validate([
            'page_id' => 'required|string',
        ]);

        $pages = session('pending_facebook_pages', []);

        try {
            $this->oauthService->SaveFacebookPage((int) auth()->id(), (string) $request->page_id, $pages);

            session()->forget(['pending_facebook_pages', 'meta_oauth_state']);

            return redirect('/akun_terhubung')
                ->with('success', 'Facebook Page berhasil disimpan.');
        } catch (Throwable $e) {

            return redirect('/akun_terhubung')
                ->with('error', 'Gagal menyimpan Facebook Page.');
        }
    }

    // ================================================================
    // Hapus
    // DELETE /akun_terhubung/{id}
    // ================================================================

    public function Hapus($id)
    {
        try {
            $this->akunTerhubungService->deleteAccount((int) auth()->id(), (int) $id);

            return redirect('/akun_terhubung')
                ->with('success', 'Akun berhasil diputuskan.');
        } catch (Throwable $e) {

            return redirect('/akun_terhubung')
                ->with('error', $e->getMessage());
        }
    }

    // ================================================================
    // CekStatus
    // GET /akun_terhubung/{id}/status
    // ================================================================

    public function CekStatus(Request $request, $id)
    {
        try {
            $result = $this->akunTerhubungService->checkStatus((int) auth()->id(), (int) $id, $this->meta);
            $status = $result['status'];
            $accountName = $result['account_name'];

            if ($request->expectsJson()) {
                return response()->json($status);
            }

            return redirect('/akun_terhubung')
                ->with('success', 'Status token akun ' . $accountName . ' berhasil diperbarui: ' . ($status['token_status'] ?? 'unknown'));
        } catch (Throwable $e) {

            if ($request->expectsJson()) {
                return response()->json([
                    'token_status' => 'expired',
                    'is_valid'     => false,
                    'message'      => 'Gagal cek status token.',
                ], 500);
            }

            return redirect('/akun_terhubung')
                ->with('error', $e->getMessage());
        }
    }

    // ================================================================
    // PRIVATE HELPER — resolveTokenStatus (moved to Model accessor)
    // ================================================================
    // Now use $akun->token_status instead of $this->resolveTokenStatus($akun)
}