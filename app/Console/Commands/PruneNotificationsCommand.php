<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune {--days=30 : Hapus notifikasi yang lebih tua dari jumlah hari ini}';

    protected $description = 'Hapus permanen notifikasi lama dari database';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $userIds = Notification::query()
            ->where('created_at', '<', $cutoff)
            ->distinct()
            ->pluck('user_id');

        $deletedCount = Notification::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        foreach ($userIds as $userId) {
            Cache::forget("user_{$userId}_unread_count");
        }

        $this->info("Prune selesai. {$deletedCount} notifikasi dihapus permanen.");

        Log::info('notifications:prune finished', [
            'deleted_count' => $deletedCount,
            'cutoff' => $cutoff->toDateTimeString(),
            'days' => $days,
        ]);

        return self::SUCCESS;
    }
}