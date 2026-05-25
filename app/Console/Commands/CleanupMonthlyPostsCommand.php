<?php

namespace App\Console\Commands;

use App\Models\PaymentSetting;
use App\Models\PostDetail;
use App\Models\PostLog;
use App\Models\PostScheduler;
use App\Models\SosialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupMonthlyPostsCommand extends Command
{
    protected $signature = 'posts:monthly-cleanup';

    protected $description = 'Cleanup bulanan data posting lama dan artefaknya';

    public function handle(): int
    {
        $currentKey = now()->format('Y-m');
        $lastCleanup = (string) PaymentSetting::get('monthly_cleanup_at', '');

        if ($lastCleanup === $currentKey) {
            $this->info('Cleanup bulanan sudah dijalankan bulan ini.');

            return self::SUCCESS;
        }

        $deletedCount = 0;

        DB::transaction(function () use (&$deletedCount, $currentKey): void {
            $postIds = SosialPost::query()->pluck('id');

            if ($postIds->isNotEmpty()) {
                $detailIds = PostDetail::whereIn('post_id', $postIds)->pluck('id');

                $paths = PostDetail::whereIn('post_id', $postIds)
                    ->whereNotNull('file_path')
                    ->pluck('file_path')
                    ->filter()
                    ->map(fn ($path) => ltrim((string) $path, '/'))
                    ->unique()
                    ->values();

                if ($paths->isNotEmpty()) {
                    Storage::disk('public')->delete($paths->all());
                }

                PostLog::whereIn('post_id', $postIds)->delete();

                if ($detailIds->isNotEmpty()) {
                    PostScheduler::whereIn('detail_id', $detailIds)->delete();
                }

                PostDetail::whereIn('post_id', $postIds)->delete();
                $deletedCount = SosialPost::whereIn('id', $postIds)->delete();
            }

            PaymentSetting::set('monthly_cleanup_at', $currentKey, 'Monthly post cleanup');
        });

        $this->info("Cleanup bulanan selesai. {$deletedCount} posting dihapus.");

        Log::info('posts:monthly-cleanup finished', [
            'deleted_count' => $deletedCount,
            'month' => $currentKey,
        ]);

        return self::SUCCESS;
    }
}