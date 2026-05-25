<?php

namespace App\Services;

use App\Models\SosialPost;
use App\Models\Notification;
use App\Models\PostLog;
use App\Models\PostScheduler;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getUserDashboardData(int $userId, array $filters = [], int $perPage = 10)
    {
        $now = now();
        $lastMonth = now()->subMonth();

        $stats = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as totalBulanIni,
                COUNT(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as totalBulanLalu,
                COUNT(CASE WHEN status = "published" AND MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as totalBerhasil,
                COUNT(CASE WHEN status IN ("published", "failed") AND MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as totalEksekusi,
                COUNT(CASE WHEN status = "failed" AND MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as totalGagal,
                COUNT(CASE WHEN status = "scheduled" THEN 1 END) as totalTunggu
            ', [
                $now->month, $now->year,
                $lastMonth->month, $lastMonth->year,
                $now->month, $now->year,
                $now->month, $now->year,
                $now->month, $now->year,
            ])
            ->first();

        $totalBulanIni = $stats->totalBulanIni ?? 0;
        $totalBulanLalu = $stats->totalBulanLalu ?? 0;
        $totalChange = $totalBulanLalu > 0
            ? round((($totalBulanIni - $totalBulanLalu) / $totalBulanLalu) * 100, 1)
            : 0;
        $totalChangeLabel = ($totalChange >= 0 ? '+' : '') . $totalChange . '% vs Bulan Lalu';

        $totalBerhasil = $stats->totalBerhasil ?? 0;
        $totalEksekusi = $stats->totalEksekusi ?? 0;
        $successRate = $totalEksekusi > 0 ? round(($totalBerhasil / $totalEksekusi) * 100, 1) : 0;
        $successRateLabel = $successRate . '% Efisiensi';

        $totalGagal = $stats->totalGagal ?? 0;
        $totalTunggu = $stats->totalTunggu ?? 0;

        $unreadCount = Cache::remember("user_{$userId}_unread_count", now()->addMinutes(5), function () use ($userId) {
            return Notification::where('user_id', $userId)->where('is_read', 0)->count();
        });

        $query = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->with(['media', 'jadwal' => function ($q) {
                $q->with('akunSosial')->where('status', '!=', 'done')->orderByDesc('scheduled_at');
            }]);

        if (!empty($filters['filter_status'])) {
            $query->where('status', $filters['filter_status']);
        }

        $urutan = $filters['urutan'] ?? 'desc';
        $query->orderBy('created_at', $urutan === 'asc' ? 'asc' : 'desc');

        $antrean = $query->paginate($perPage);

        $postLogs = [];
        $postIds = $antrean->getCollection()->pluck('id')->all();
        if (!empty($postIds)) {
            $logs = PostLog::whereIn('post_id', $postIds)
                ->where('status', 'success')
                ->latest('executed_at')
                ->get()
                ->groupBy('post_id');

            foreach ($logs as $postId => $postLogGroup) {
                $postLogs[$postId] = $postLogGroup->first();
            }
        }

        return compact(
            'totalBulanIni', 'totalChangeLabel', 'totalBerhasil', 'successRate', 'successRateLabel', 'totalGagal', 'totalTunggu',
            'antrean', 'urutan', 'unreadCount', 'postLogs'
        );
    }

    public function getNotifications(int $userId, Request $request)
    {
        $notifikasi = Notification::where('user_id', $userId)
            ->latest()
            ->paginate(15);

        $unreadCount = Cache::remember(
            "user_{$userId}_unread_count",
            now()->addMinutes(5),
            fn() => Notification::where('user_id', $userId)->where('is_read', 0)->count()
        );

        return ['notifikasi' => $notifikasi, 'unreadCount' => $unreadCount];
    }

    public function markNotificationRead(int $userId, int $id): void
    {
        $notif = Notification::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $notif->update(['is_read' => 1, 'read_at' => now()]);

        Cache::forget("user_{$userId}_unread_count");
    }

    public function markAllNotificationsRead(int $userId): void
    {
        Notification::where('user_id', $userId)->where('is_read', 0)->update(['is_read' => 1, 'read_at' => now()]);

        Cache::forget("user_{$userId}_unread_count");
    }

    public function getAdminDashboardData(int $perPage = 10)
    {
        $adminStats = DB::table('post_logs')
            ->selectRaw('
                COUNT(CASE WHEN status = "success" AND MONTH(executed_at) = ? THEN 1 END) as berhasil,
                COUNT(CASE WHEN status = "failed" AND MONTH(executed_at) = ? THEN 1 END) as gagal,
                COUNT(CASE WHEN status = "failed" AND DATE(executed_at) = ? THEN 1 END) as errorHariIni
            ', [now()->month, now()->month, today()->toDateString()])
            ->first();

        $totalUser = Cache::remember('admin_total_active_users', now()->addHours(1), fn() => User::where('role', 'user')->where('is_active', 1)->count());

        $totalPostBulanIni = Cache::remember('admin_total_posts_month', now()->addHours(1), fn() => SosialPost::whereNull('deleted_at')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count());

        $totalBerhasil = $adminStats->berhasil ?? 0;
        $totalGagal = $adminStats->gagal ?? 0;
        $totalTunggu = PostScheduler::whereIn('status', ['pending', 'processing'])->count();

        $totalEksekusi = $totalBerhasil + $totalGagal;
        $successRate = $totalEksekusi > 0 ? round(($totalBerhasil / $totalEksekusi) * 100, 1) : 0;

        $errorHariIni = $adminStats->errorHariIni ?? 0;

        $logTerbaru = PostLog::with(['post' => fn($q) => $q->select('id', 'user_id', 'caption', 'status'), 'post.user' => fn($q) => $q->select('id', 'name', 'email'), 'schedule.akunSosial'])
            ->where('status', 'failed')
            ->latest('executed_at')
            ->take(5)
            ->get();

        $antreanAdmin = PostScheduler::with(['detail' => fn($q) => $q->select('id', 'post_id'), 'detail.post' => fn($q) => $q->select('id', 'user_id', 'caption'), 'detail.post.user' => fn($q) => $q->select('id', 'name'), 'akunSosial' => fn($q) => $q->select('id', 'platform', 'username')])
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('scheduled_at')
            ->paginate($perPage);

        return compact('totalUser', 'totalPostBulanIni', 'totalBerhasil', 'totalGagal', 'totalTunggu', 'successRate', 'errorHariIni', 'logTerbaru', 'antreanAdmin');
    }

    public function getStatistics(
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai
    ): array {
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

        $perPlatform = PostLog::with('schedule.akunSosial')
            ->whereBetween('executed_at', [$dari, $sampai])
            ->get()
            ->groupBy(fn($log) => $log->schedule->akunSosial->platform ?? 'unknown')
            ->map(fn($group) => [
                'total'    => $group->count(),
                'berhasil' => $group->where('status', 'success')->count(),
                'gagal'    => $group->where('status', 'failed')->count(),
            ]);

        $userAktif = SosialPost::whereNull('deleted_at')
            ->whereBetween('created_at', [$dari, $sampai])
            ->selectRaw('user_id, COUNT(*) as total_post')
            ->groupBy('user_id')
            ->orderByDesc('total_post')
            ->with('user:id,name,email')
            ->take(5)
            ->get();

        $ringkasan = [
            'total_post'    => SosialPost::whereNull('deleted_at')->whereBetween('created_at', [$dari, $sampai])->count(),
            'total_berhasil'=> PostLog::where('status', 'success')->whereBetween('executed_at', [$dari, $sampai])->count(),
            'total_gagal'   => PostLog::where('status', 'failed')->whereBetween('executed_at', [$dari, $sampai])->count(),
            'total_user'    => User::where('is_active', 1)->count(),
        ];

        return compact('grafikHarian', 'perPlatform', 'userAktif', 'ringkasan');
    }
}
