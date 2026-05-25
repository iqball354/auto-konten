<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ExportService;
use Throwable;

class ekspor_dataController extends Controller
{
    public function __construct(private readonly ExportService $exportService)
    {
    }

    // ================================================================
    // ShowExport
    // GET /ekspor_data
    // Halaman ekspor data — statistik + filter rentang tanggal
    // ================================================================

    public function ShowExport(Request $request)
    {
        try {
            $userId = Auth::id();

            $filters = $request->only(['periode', 'dari', 'sampai', 'akun_id', 'status', 'tipe_data']);
            $range = $this->exportService->resolveDateRange($filters);
            $stats = $this->exportService->getExportStatistics($userId, $filters);
            $riwayat = $this->exportService->getExportHistory($userId, $filters);
            $postLogs = $this->exportService->getPostLogsMap($riwayat->getCollection()->pluck('id')->all());

            return view('ekspor_data', [
                'riwayat' => $riwayat,
                'postLogs' => $postLogs,
                'akunList' => $this->exportService->getAvailableAccounts($userId),
                'periode' => $filters['periode'] ?? '30d',
                'tipeData' => $filters['tipe_data'] ?? 'postingan',
                'akunId' => $filters['akun_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'dari' => $range['dari'],
                'sampai' => $range['sampai'],
                'stats' => $stats,
                'statTotal' => $stats['statTotal'],
                'statTotalChange' => $stats['statTotalChange'],
                'statTotalChangeLabel' => $stats['statTotalChangeLabel'],
                'statBerhasil' => $stats['statBerhasil'],
                'statGagal' => $stats['statGagal'],
                'statTunggu' => $stats['statTunggu'],
                'statSuccessRate' => $stats['statSuccessRate'],
                'statSuccessRateLabel' => $stats['statSuccessRateLabel'],
                'statSuccessChangeLabel' => $stats['statSuccessChangeLabel'],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load export dashboard.', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['periode', 'dari', 'sampai', 'akun_id', 'status', 'tipe_data']),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat halaman ekspor data. Silakan coba lagi.');
        }
    }

    // ================================================================
    // ExportExcel
    // POST /ekspor_data/excel
    // Export data riwayat posting ke file Excel (.xlsx)
    // ================================================================

    public function ExportExcel(Request $request)
    {
        try {
            $request->validate([
                'dari'   => 'nullable|date',
                'sampai' => 'nullable|date|after_or_equal:dari',
                'status' => 'nullable|in:success,failed',
                'akun_id' => 'nullable|integer|exists:sosial_accounts,id',
                'tipe_data' => 'nullable|in:postingan,interaksi,log_aktivitas',
                'format' => 'nullable|in:excel,csv',
            ]);

            return $this->exportService->exportCsv((int) Auth::id(), $request->only(['dari', 'sampai', 'status', 'akun_id', 'tipe_data', 'format']));
        } catch (Throwable $e) {
            Log::error('Failed to export csv data.', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['dari', 'sampai', 'status', 'akun_id', 'tipe_data', 'format']),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal export data CSV. Silakan coba lagi.');
        }
    }

    // ================================================================
    // ExportPdf
    // POST /ekspor_data/pdf
    // Export ringkasan statistik ke PDF (menggunakan view + print CSS)
    // ================================================================

    public function ExportPdf(Request $request)
    {
        try {
            $request->validate([
                'dari'   => 'nullable|date',
                'sampai' => 'nullable|date|after_or_equal:dari',
                'akun_id' => 'nullable|integer|exists:sosial_accounts,id',
                'tipe_data' => 'nullable|in:postingan,interaksi,log_aktivitas',
            ]);

            return $this->exportService->exportPdf((int) Auth::id(), $request->only(['dari', 'sampai', 'akun_id', 'tipe_data']));
        } catch (Throwable $e) {
            Log::error('Failed to export pdf data.', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['dari', 'sampai', 'akun_id', 'tipe_data']),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal export PDF. Silakan coba lagi.');
        }
    }

    // ================================================================
    // ShowAdminLog
    // GET /admin/log
    // Semua log posting dari semua user — khusus admin
    // ================================================================

    public function ShowAdminLog(Request $request)
    {
        try {
            $data = $this->exportService->getAdminLogs($request->only(['status', 'tanggal', 'platform', 'user_id']));

            return view('admin.log', $data);
        } catch (Throwable $e) {
            Log::error('Failed to load admin logs.', [
                'user_id' => Auth::id(),
                'filters' => $request->only(['status', 'tanggal', 'platform', 'user_id']),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat data log admin. Silakan coba lagi.');
        }
    }

    // ================================================================
    // ShowLogDetail
    // GET /admin/log/{id}
    // Detail satu log posting — termasuk raw response dari Meta API
    // ================================================================

    public function ShowLogDetail($id)
    {
        try {
            $log = $this->exportService->getAdminLogDetail((int) $id);

            return view('admin.log_detail', compact('log'));
        } catch (Throwable $e) {
            Log::error('Failed to load admin log detail.', [
                'user_id' => Auth::id(),
                'log_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat detail log. Silakan coba lagi.');
        }
    }
}