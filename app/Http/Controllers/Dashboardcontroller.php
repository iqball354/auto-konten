<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SosialPost;
use App\Models\PostScheduler;
use App\Models\PostLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\DashboardService;

class Dashboardcontroller extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    // ================================================================
    // ShowDashboard
    // GET /dashboard
    // Halaman utama — 4 kartu statistik + tabel antrean konten terjadwal
    // ================================================================

    public function ShowDashboard(Request $request)
    {
        $userId = Auth::id();

        $data = $this->dashboardService->getUserDashboardData($userId, $request->all());

        return view('dashboard', $data);
    }

    // ================================================================
    // ShowNotifikasi
    // GET /notifikasi
    // Daftar semua notifikasi milik user
    // ================================================================

    public function ShowNotifikasi(Request $request)
    {
        $userId = Auth::id();

        $data = $this->dashboardService->getNotifications($userId, $request);

        return view('notifikasi', $data);
    }

    // ================================================================
    // TandaiBaca
    // POST /notifikasi/{id}/baca
    // Tandai satu notifikasi sudah dibaca
    // ================================================================

    public function TandaiBaca($id)
    {
        $userId = Auth::id();

        $this->dashboardService->markNotificationRead($userId, (int) $id);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('notifikasi')->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    // ================================================================
    // TandaiBacaSemua
    // POST /notifikasi/baca-semua
    // Tandai semua notifikasi sudah dibaca
    // ================================================================

    public function TandaiBacaSemua()
    {
        $userId = Auth::id();

        $this->dashboardService->markAllNotificationsRead($userId);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('notifikasi')->with('success', 'Semua notifikasi sudah ditandai dibaca.');
    }

    // ================================================================
    // ShowAdminDashboard
    // GET /admin/dashboard
    // Dashboard khusus admin — statistik seluruh platform
    // ================================================================

    public function ShowAdminDashboard()
    {
        $data = $this->dashboardService->getAdminDashboardData();

        return view('admin.dashboard', $data);
    }

    // ================================================================
    // ShowStatistik
    // GET /admin/statistik
    // Grafik & data statistik penggunaan platform (semua user)
    // ================================================================

    public function ShowStatistik(Request $request)
    {
        // Rentang tanggal default: 30 hari terakhir
        $dari   = $request->filled('dari')
            ? \Carbon\Carbon::parse($request->dari)->startOfDay()
            : now()->subDays(29)->startOfDay();

        $sampai = $request->filled('sampai')
            ? \Carbon\Carbon::parse($request->sampai)->endOfDay()
            : now()->endOfDay();

        // ── Data grafik posting per hari ────────────────────────────
        $grafikHarian = PostLog::whereBetween('executed_at', [$dari, $sampai])
            ->selectRaw("DATE(executed_at) as tanggal, status, COUNT(*) as total")
            ->groupBy('tanggal', 'status')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal')
            ->map(fn($group) => [
                'berhasil' => $group->where('status', 'success')->sum('total'),
                'gagal'    => $group->where('status', 'failed')->sum('total'),
            ]);

        // ── Breakdown per platform ───────────────────────────────────
        $perPlatform = PostLog::with('schedule.akunSosial')
            ->whereBetween('executed_at', [$dari, $sampai])
            ->get()
            ->groupBy(fn($log) => $log->schedule->akunSosial->platform ?? 'unknown')
            ->map(fn($group) => [
                'total'    => $group->count(),
                'berhasil' => $group->where('status', 'success')->count(),
                'gagal'    => $group->where('status', 'failed')->count(),
            ]);

        // ── User paling aktif (top 5) ────────────────────────────────
        $userAktif = SosialPost::whereNull('deleted_at')
            ->whereBetween('created_at', [$dari, $sampai])
            ->selectRaw('user_id, COUNT(*) as total_post')
            ->groupBy('user_id')
            ->orderByDesc('total_post')
            ->with('user:id,name,email')
            ->take(5)
            ->get();

        // ── Ringkasan angka ──────────────────────────────────────────
        $ringkasan = [
            'total_post'    => SosialPost::whereNull('deleted_at')->whereBetween('created_at', [$dari, $sampai])->count(),
            'total_berhasil'=> PostLog::where('status', 'success')->whereBetween('executed_at', [$dari, $sampai])->count(),
            'total_gagal'   => PostLog::where('status', 'failed')->whereBetween('executed_at', [$dari, $sampai])->count(),
            'total_user'    => User::where('is_active', 1)->count(),
        ];

        $stats = $this->dashboardService->getStatistics($dari, $sampai);

        return view('admin.statistik', array_merge($stats, compact('dari', 'sampai')));
    }
}