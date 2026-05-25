<?php

namespace App\Services;

use App\Models\PostLog;
use App\Models\PostScheduler;
use App\Models\SosialAccount;
use App\Models\SosialPost;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function resolveDateRange(array $filters): array
    {
        if (!empty($filters['dari']) || !empty($filters['sampai'])) {
            $dari = !empty($filters['dari'])
                ? Carbon::parse($filters['dari'])->startOfDay()
                : now()->subDays(29)->startOfDay();

            $sampai = !empty($filters['sampai'])
                ? Carbon::parse($filters['sampai'])->endOfDay()
                : now()->endOfDay();

            return [
                'dari' => $dari,
                'sampai' => $sampai,
            ];
        }

        $periode = (string) ($filters['periode'] ?? '30d');
        $rangeDays = match ($periode) {
            '7d' => 6,
            '90d' => 89,
            default => 29,
        };

        return [
            'dari' => now()->subDays($rangeDays)->startOfDay(),
            'sampai' => now()->endOfDay(),
        ];
    }

    public function getAvailableAccounts(int $userId)
    {
        return SosialAccount::where('user_id', $userId)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get();
    }

    public function getExportStatistics(int $userId, array $filters): array
    {
        $range = $this->resolveDateRange($filters);
        $dari = $range['dari'];
        $sampai = $range['sampai'];
        $akunId = $filters['akun_id'] ?? null;

        $statTotal = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$dari, $sampai])
            ->count();

        $bulanLalu = now()->subMonth();
        $totalBulanLalu = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereMonth('created_at', $bulanLalu->month)
            ->whereYear('created_at', $bulanLalu->year)
            ->count();

        $statTotalChange = $totalBulanLalu > 0
            ? (($statTotal - $totalBulanLalu) / $totalBulanLalu)
            : 0;

        $statBerhasilQuery = PostLog::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'success')
            ->whereBetween('executed_at', [$dari, $sampai]);

        $statGagalQuery = PostLog::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'failed')
            ->whereBetween('executed_at', [$dari, $sampai]);

        if (!empty($akunId)) {
            $statBerhasilQuery->whereHas('schedule', fn ($q) => $q->where('sosial_account_id', $akunId));
            $statGagalQuery->whereHas('schedule', fn ($q) => $q->where('sosial_account_id', $akunId));
        }

        $statBerhasil = $statBerhasilQuery->count();
        $statGagal = $statGagalQuery->count();

        $statTunggu = PostScheduler::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $totalEksekusi = $statBerhasil + $statGagal;
        $statSuccessRate = $totalEksekusi > 0
            ? round(($statBerhasil / $totalEksekusi) * 100, 1)
            : 0;

        return [
            'statTotal' => $statTotal,
            'statBerhasil' => $statBerhasil,
            'statGagal' => $statGagal,
            'statTunggu' => $statTunggu,
            'statSuccessRate' => $statSuccessRate,
            'statTotalChange' => $statTotalChange,
            'statTotalChangeLabel' => $this->formatPercentageLabel($statTotalChange),
            'statSuccessRateLabel' => $statSuccessRate . '%',
            'statSuccessChangeLabel' => $statSuccessRate . '% Efisiensi',
        ];
    }

    public function getExportHistory(int $userId, array $filters): LengthAwarePaginator
    {
        $range = $this->resolveDateRange($filters);
        $dari = $range['dari'];
        $sampai = $range['sampai'];

        $riwayatQuery = SosialPost::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->with(['jadwal.akunSosial'])
            ->whereBetween('created_at', [$dari, $sampai])
            ->latest('created_at');

        if (!empty($filters['akun_id'])) {
            $riwayatQuery->whereHas('jadwal', fn ($q) => $q->where('sosial_account_id', $filters['akun_id']));
        }

        if (!empty($filters['status'])) {
            $riwayatQuery->where('status', $filters['status']);
        }

        return $riwayatQuery->paginate(15);
    }

    public function getPostLogsMap(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $logs = PostLog::whereIn('post_id', $postIds)
            ->where('status', 'success')
            ->latest('executed_at')
            ->get()
            ->groupBy('post_id');

        $postLogs = [];

        foreach ($logs as $postId => $postLogGroup) {
            $postLogs[$postId] = $postLogGroup->first();
        }

        return $postLogs;
    }

    public function exportCsv(int $userId, array $filters): StreamedResponse
    {
        $range = $this->resolveDateRange($filters);
        $dari = $range['dari'];
        $sampai = $range['sampai'];

        $query = PostLog::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->with(['post', 'schedule.akunSosial'])
            ->whereBetween('executed_at', [$dari, $sampai]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['akun_id'])) {
            $query->whereHas('schedule', fn ($q) => $q->where('sosial_account_id', $filters['akun_id']));
        }

        $data = $query->orderBy('executed_at', 'desc')->get();
        $filename = 'riwayat_posting_' . now()->format('Ymd_His') . '_csv.csv';

        return Response::streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'No',
                'Judul Konten',
                'Platform',
                'Akun',
                'Status',
                'Waktu Eksekusi',
                'ID Postingan di Platform',
                'Kode Error',
                'Pesan Error',
            ]);

            foreach ($data as $index => $log) {
                fputcsv($handle, [
                    $index + 1,
                    Str::limit($log->post->caption ?? '-', 60),
                    $log->schedule->akunSosial->platform ?? '-',
                    $log->schedule->akunSosial->username ?? '-',
                    $log->status,
                    $log->executed_at?->format('d/m/Y H:i'),
                    $log->platform_post_id ?? '-',
                    $log->error_code ?? '-',
                    $log->error_message ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf(int $userId, array $filters)
    {
        $range = $this->resolveDateRange($filters);
        $dari = $range['dari'];
        $sampai = $range['sampai'];
        $stat = $this->getExportStatistics($userId, $filters);

        $perPlatformQuery = PostLog::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->with('schedule.akunSosial')
            ->whereBetween('executed_at', [$dari, $sampai]);

        if (!empty($filters['akun_id'])) {
            $perPlatformQuery->whereHas('schedule', fn ($q) => $q->where('sosial_account_id', $filters['akun_id']));
        }

        $perPlatform = $perPlatformQuery->get()
            ->groupBy(fn ($log) => $log->schedule->akunSosial->platform ?? 'unknown')
            ->map(fn ($group) => [
                'total' => $group->count(),
                'berhasil' => $group->where('status', 'success')->count(),
                'gagal' => $group->where('status', 'failed')->count(),
            ]);

        $riwayat = PostLog::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->with(['post', 'schedule.akunSosial'])
            ->whereBetween('executed_at', [$dari, $sampai])
            ->when(!empty($filters['akun_id']), fn ($query) => $query->whereHas('schedule', fn ($q) => $q->where('sosial_account_id', $filters['akun_id'])))
            ->orderBy('executed_at', 'desc')
            ->get();

        $filename = 'laporan_ekspor_' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('ekspor_pdf', [
            'totalPost' => $stat['statTotal'],
            'berhasil' => $stat['statBerhasil'],
            'gagal' => $stat['statGagal'],
            'successRate' => $stat['statSuccessRate'],
            'perPlatform' => $perPlatform,
            'riwayat' => $riwayat,
            'dari' => $dari,
            'sampai' => $sampai,
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    public function getAdminLogs(array $filters): array
    {
        $query = PostLog::with(['post.user', 'schedule.akunSosial'])
            ->latest('executed_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tanggal'])) {
            $query->whereDate('executed_at', $filters['tanggal']);
        }

        if (!empty($filters['platform'])) {
            $query->whereHas('schedule.akunSosial', fn ($q) => $q->where('platform', $filters['platform']));
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('post', fn ($q) => $q->where('user_id', $filters['user_id']));
        }

        $logs = $query->paginate(20);

        $statAdmin = [
            'total' => PostLog::count(),
            'berhasil' => PostLog::where('status', 'success')->count(),
            'gagal' => PostLog::where('status', 'failed')->count(),
            'hari_ini' => PostLog::whereDate('executed_at', today())->count(),
        ];

        return [
            'logs' => $logs,
            'statAdmin' => $statAdmin,
        ];
    }

    public function getAdminLogDetail(int $id): PostLog
    {
        return PostLog::with([
            'post.user',
            'post',
            'schedule.akunSosial',
        ])->findOrFail($id);
    }

    private function formatPercentageLabel(float $value): string
    {
        return ($value >= 0 ? '+' : '') . number_format($value * 100, 1) . '%';
    }
}
