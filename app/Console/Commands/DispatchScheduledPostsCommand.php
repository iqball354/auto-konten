<?php

namespace App\Console\Commands;

use App\Jobs\PublishPostJob;
use App\Models\PostScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledPostsCommand extends Command
{
    protected $signature = 'posts:dispatch-scheduled';

    protected $description = 'Dispatch jadwal posting yang sudah jatuh tempo';

    public function handle(): int
    {
        Log::info('Scheduler posts:dispatch-scheduled started.');

        $dueSchedules = PostScheduler::query()
            ->with('detail.post')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        if ($dueSchedules->isEmpty()) {
            $this->info('Tidak ada jadwal yang jatuh tempo.');
            Log::info('Scheduler posts:dispatch-scheduled: no due schedules.');
            return self::SUCCESS;
        }

        foreach ($dueSchedules as $schedule) {
            $post = $schedule->detail?->post;

            if (!$post || $post->deleted_at) {
                $schedule->update([
                    'status'      => 'failed',
                    'executed_at' => now(),
                ]);

                Log::warning("Scheduler: Jadwal #{$schedule->id} gagal karena detail/post tidak ditemukan atau sudah dihapus.");
                continue;
            }

            $schedule->update(['status' => 'processing']);

            try {
                    // Kirim ke queue agar publish diproses worker, bukan blokir scheduler.
                    PublishPostJob::dispatch($post->id, $schedule->id);
            } catch (\Throwable $e) {
                $schedule->update([
                    'status'      => 'failed',
                    'executed_at' => now(),
                ]);

                Log::error("Scheduler: Jadwal #{$schedule->id} gagal.", [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                ]);

                $this->error("Jadwal #{$schedule->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("{$dueSchedules->count()} jadwal diproses.");
        Log::info("Scheduler posts:dispatch-scheduled finished. Processed {$dueSchedules->count()} schedules.");

        return self::SUCCESS;
    }
}
