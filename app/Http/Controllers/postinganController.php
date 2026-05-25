<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SosialPost;
use App\Services\PostingService;
use App\Services\JadwalService;
use App\Services\MediaService;
use App\Services\AkunTerhubungService;
use Illuminate\Support\Facades\Log;


class postinganController extends Controller
{
    public function __construct(
        private readonly PostingService $postingService,
        private readonly JadwalService $jadwalService,
        private readonly MediaService $mediaService,
        private readonly AkunTerhubungService $akunTerhubungService
    ) {
    }

    // ================================================================
    // ShowPosting
    // GET /postingan
    // ================================================================

    public function ShowPosting(Request $request)
    {
        $userId = (int) auth()->id();

        $filters = $request->only(['status', 'platform', 'tanggal']);
        $data = $this->postingService->getPostingPageData($userId, $filters);

        return view('postingan', $data);
    }

    public function getPostInfo($postId)
    {
        return response()->json($this->postingService->getPostInfoOptimized((int) $postId));
    }

    // ================================================================
    // StorePosting
    // POST /postingan
    // ================================================================

    public function StorePosting(Request $request)
    {
        $request->validate([
            'caption'     => 'required|string',
            'hashtags'    => 'nullable|string',
            'submit_mode' => 'nullable|in:draft,publish',
            'platforms'   => 'required|array|min:1',
            'platforms.*' => 'in:instagram,facebook',
            'text_template' => 'nullable|string|in:classic_aurora,sunset_fade,royal_plum,emerald_wave,midnight_blue,orange_pop,mono_ink,neon_blend',
            'template_text' => 'nullable|string|max:220',
            'media'       => 'nullable|array',
            'media.*'     => 'file|mimes:jpg,jpeg,png,webp,mp4|max:102400',
        ]);

        try {
            $message = $this->postingService->StorePosting($request, (int) auth()->id());

            return redirect()->route('postingan')->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('StorePosting gagal.', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('postingan')
                ->with('error', 'Gagal menyimpan postingan. Silakan coba lagi.');
        }
    }

    // ================================================================
    // ShowEditPosting
    // GET /postingan/{id}/edit
    // ================================================================

    public function ShowEditPosting($id)
    {
        $userId = (int) auth()->id();

        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->with('media')
            ->firstOrFail();

        $akun_terhubung = $this->akunTerhubungService->getActiveAccounts($userId);

        // Hanya draft dan scheduled yang bisa diedit
        if (!in_array($postingan->status, ['draft', 'scheduled'])) {
            return redirect()->route('postingan')
                ->with('error', 'Konten yang sudah dipublish atau gagal tidak bisa diedit.');
        }

        // Get PostLog data untuk generate platform URLs (jika ada published log)
        $postLogs = [];
        $postLog = \App\Models\PostLog::where('post_id', $id)
            ->where('status', 'success')
            ->latest('executed_at')
            ->first();
        if ($postLog) {
            $postLogs[$id] = $postLog;
        }

        return view('postingan_update', compact('postingan', 'akun_terhubung', 'postLogs'));
    }

    // ================================================================
    // UpdatePosting
    // PUT /postingan/{id}
    // ================================================================

    public function UpdatePosting(Request $request, $id)
    {
        $request->validate([
            'caption'     => 'required|string',
            'hashtags'    => 'nullable|string',
            'platforms'   => 'required|array|min:1',
            'platforms.*' => 'in:instagram,facebook',
            'text_template' => 'nullable|string|in:classic_aurora,sunset_fade,royal_plum,emerald_wave,midnight_blue,orange_pop,mono_ink,neon_blend',
            'template_text' => 'nullable|string|max:220',
        ]);

        try {
            $this->postingService->UpdatePosting($request, (int) $id, (int) auth()->id());

            return redirect()->route('postingan')->with('success', 'Postingan berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('UpdatePosting gagal.', [
                'user_id' => auth()->id(),
                'post_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('postingan')
                ->with('error', 'Gagal memperbarui postingan. Silakan coba lagi.');
        }
    }

    // ================================================================
    // HapusPosting
    // DELETE /postingan/{id}
    // ================================================================

    public function HapusPosting($id)
    {
        try {
            $this->postingService->HapusPosting((int) $id, (int) auth()->id());

            return redirect()->route('postingan')->with('success', 'Postingan berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('HapusPosting gagal.', [
                'user_id' => auth()->id(),
                'post_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('postingan')
                ->with('error', 'Gagal menghapus postingan.');
        }
    }

    // ================================================================
    // UploadMedia
    // POST /postingan/{id}/media
    // ================================================================

    public function UploadMedia(Request $request, $id)
    {
        $request->validate([
            'media' => 'required|file|mimes:jpg,jpeg,png,webp,mp4|max:102400',
        ]);

        try {
            $media = $this->mediaService->UploadMedia($request, (int) $id, (int) auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Media berhasil diupload.',
                'data'    => $media,
            ]);
        } catch (\Throwable $e) {
            Log::error('UploadMedia gagal.', [
                'user_id' => auth()->id(),
                'post_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload media gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    // ================================================================
    // HapusMedia
    // DELETE /postingan/{id}/media/{mediaId}
    // ================================================================

    public function HapusMedia($id, $mediaId)
    {
        try {
            $this->mediaService->HapusMedia((int) $id, (int) $mediaId, (int) auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Media berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            Log::error('HapusMedia gagal.', [
                'user_id'  => auth()->id(),
                'post_id'  => $id,
                'media_id' => $mediaId,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hapus media gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    // ================================================================
    // PublishNow
    // POST /postingan/{id}/publish-now
    // ================================================================

    public function PublishNow($id)
    {
        try {
            $isPublished = $this->postingService->PublishNow((int) $id, (int) auth()->id());
        } catch (\Throwable $e) {
            return redirect()->route('postingan')
                ->with('error', 'Publish sekarang gagal: ' . $e->getMessage());
        }

        return redirect()->route('postingan')->with('success', $isPublished
            ? 'Postingan berhasil dipublish sekarang.'
            : 'Permintaan publish sudah dijalankan, tetapi status belum final.');
    }

    // ================================================================
    // ShowJadwal
    // GET /jadwal
    // ================================================================

    public function ShowJadwal(Request $request)
    {
        $userId = (int) auth()->id();

        $query = \App\Models\PostScheduler::join('post_detail', 'post_scheduler.detail_id', '=', 'post_detail.id')
            ->join('sosial_post', 'post_detail.post_id', '=', 'sosial_post.id')
            ->where('sosial_post.user_id', $userId)
            ->whereNull('sosial_post.deleted_at')
            ->select('post_scheduler.*')
            ->with(['detail.post.media', 'akunSosial'])
            ->latest('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('scheduled_at', $request->tanggal);
        }

        $jadwal = $query->paginate(10);

        $akun_list = $this->akunTerhubungService->getActiveAccounts($userId);

        return view('jadwal', compact('jadwal', 'akun_list'));
    }

    // ================================================================
    // StoreJadwal
    // POST /jadwal
    // ================================================================

    public function StoreJadwal(Request $request)
    {
        $request->validate([
            'caption'           => 'required|string',
            'hashtags'          => 'nullable|string',
            'platforms'         => 'required|array|min:1',
            'platforms.*'       => 'in:instagram,facebook',
            'text_template'     => 'nullable|string|in:classic_aurora,sunset_fade,royal_plum,emerald_wave,midnight_blue,orange_pop,mono_ink,neon_blend',
            'template_text'     => 'nullable|string|max:220',
            'social_account_id' => 'required|integer|exists:sosial_accounts,id',
            'scheduled_at'      => 'required|date|after:now',
            'flow_enabled'      => 'nullable|boolean',
            'flow_days'         => 'nullable|integer|min:2|max:60',
            'flow_interval_days'=> 'nullable|integer|min:1|max:30',
            'media'             => 'nullable|array',
            'media.*'           => 'file|mimes:jpg,jpeg,png,webp,mp4|max:102400',
        ]);

        try {
            $this->jadwalService->StoreJadwal($request, (int) auth()->id());

            return redirect()->route('postingan')->with('success', 'Jadwal posting berhasil dibuat.');
        } catch (\Throwable $e) {
            Log::error('StoreJadwal gagal.', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            return redirect()->route('postingan')
                ->with('error', 'Gagal membuat jadwal posting.');
        }
    }

    // ================================================================
    // UpdateJadwal
    // PUT /jadwal/{id}
    // ================================================================

    public function UpdateJadwal(Request $request, $id)
    {
        $request->validate([
            'scheduled_at'      => 'required|date|after:now',
            'social_account_id' => 'nullable|integer|exists:sosial_accounts,id',
        ]);

        try {
            $this->jadwalService->UpdateJadwal($request, (int) $id, (int) auth()->id());

            return redirect()->route('jadwal')->with('success', 'Jadwal berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('UpdateJadwal gagal.', [
                'user_id'      => auth()->id(),
                'schedule_id'  => $id,
                'error'        => $e->getMessage(),
            ]);

            return redirect()->route('jadwal')
                ->with('error', 'Gagal memperbarui jadwal.');
        }
    }

    // ================================================================
    // BatalJadwal
    // DELETE /jadwal/{id}
    // ================================================================

    public function BatalJadwal($id)
    {
        try {
            $this->jadwalService->BatalJadwal((int) $id, (int) auth()->id());

            return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dibatalkan.');
        } catch (\Throwable $e) {
            Log::error('BatalJadwal gagal.', [
                'user_id'     => auth()->id(),
                'schedule_id' => $id,
                'error'       => $e->getMessage(),
            ]);

            return redirect()->route('jadwal')
                ->with('error', 'Gagal membatalkan jadwal.');
        }
    }

    // ================================================================
    // ShowKalender
    // GET /jadwal/kalender
    // Return JSON untuk FullCalendar.js
    // ================================================================

    public function ShowKalender()
    {
        return response()->json($this->jadwalService->getCalendarEvents((int) auth()->id()));
    }

    // ================================================================
    // ShowRiwayat
    // GET /riwayat
    // ================================================================

    public function ShowRiwayat(Request $request)
    {
        $userId = (int) auth()->id();

        $riwayat = $this->postingService->getRiwayatData($userId, $request->only(['status', 'tanggal', 'platform']));

        return view('riwayat', compact('riwayat'));
    }

    // ================================================================
    // ShowLogDetail
    // GET /riwayat/{id}
    // ================================================================

    public function ShowLogDetail($id)
    {
        $userId = (int) auth()->id();

        $log = $this->postingService->getLogDetail($userId, (int) $id);

        return view('riwayat_detail', compact('log'));
    }

}