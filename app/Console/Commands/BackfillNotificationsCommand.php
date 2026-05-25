<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\PostLog;
use App\Models\SosialAccount;
use Illuminate\Console\Command;

class BackfillNotificationsCommand extends Command
{
    protected $signature = 'notifications:backfill {--dry-run : Simulasi tanpa insert data}';

    protected $description = 'Backfill notifikasi lama untuk postingan dan akun terhubung';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Mode dry-run aktif. Tidak ada data yang disimpan.'
            : 'Menjalankan backfill notifikasi...');

        $postingCreated = 0;
        $postingSkipped = 0;

        PostLog::query()
            ->with(['post', 'schedule.akunSosial'])
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($dryRun, &$postingCreated, &$postingSkipped) {
                foreach ($logs as $log) {
                    $schedule = $log->schedule;
                    $post = $log->post;

                    if (!$post || !$post->user_id) {
                        $postingSkipped++;
                        continue;
                    }

                    $type = $log->status === 'success'
                        ? 'posting_success'
                        : ($log->status === 'failed' ? 'posting_failed' : null);

                    if (!$type) {
                        $postingSkipped++;
                        continue;
                    }

                    $exists = Notification::query()
                        ->where('user_id', $post->user_id)
                        ->where('type', $type)
                        ->where('data->post_log_id', $log->id)
                        ->exists();

                    if ($exists) {
                        $postingSkipped++;
                        continue;
                    }

                    $platform = ucfirst((string) ($schedule?->akunSosial?->platform ?? 'facebook'));
                    $message = $type === 'posting_success'
                        ? "Konten Anda berhasil dipublish ke {$platform}."
                        : 'Konten gagal dipublish: ' . ($log->error_message ?: 'Tanpa detail error.');

                    if (!$dryRun) {
                        Notification::create([
                            'user_id' => $post->user_id,
                            'type'    => $type,
                            'title'   => $type === 'posting_success' ? 'Postingan berhasil dipublish' : 'Postingan gagal dipublish',
                            'message' => $message,
                            'data'    => [
                                'post_id'     => $post->id,
                                'post_log_id' => $log->id,
                            ],
                        ]);
                    }

                    $postingCreated++;
                }
            });

        $accountCreated = 0;
        $accountSkipped = 0;

        SosialAccount::query()
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('id')
            ->chunkById(200, function ($accounts) use ($dryRun, &$accountCreated, &$accountSkipped) {
                foreach ($accounts as $account) {
                    $exists = Notification::query()
                        ->where('user_id', $account->user_id)
                        ->where('type', 'account_connected')
                        ->where('data->sosial_account_id', $account->id)
                        ->exists();

                    if ($exists) {
                        $accountSkipped++;
                        continue;
                    }

                    $accountName = $account->username ?: $account->platform_user_id;

                    if (!$dryRun) {
                        Notification::create([
                            'user_id' => $account->user_id,
                            'type'    => 'account_connected',
                            'title'   => 'Akun berhasil terhubung',
                            'message' => ucfirst((string) $account->platform) . ' account "' . $accountName . '" sudah terhubung dan aktif.',
                            'data'    => [
                                'platform'         => $account->platform,
                                'name'             => $accountName,
                                'sosial_account_id'=> $account->id,
                            ],
                        ]);
                    }

                    $accountCreated++;
                }
            });

        $this->line('--- Ringkasan Backfill ---');
        $this->line('Postingan dibuat : ' . $postingCreated);
        $this->line('Postingan dilewati : ' . $postingSkipped);
        $this->line('Akun dibuat : ' . $accountCreated);
        $this->line('Akun dilewati : ' . $accountSkipped);

        if ($dryRun) {
            $this->info('Dry-run selesai. Jalankan tanpa --dry-run untuk menyimpan notifikasi.');
        } else {
            $this->info('Backfill notifikasi selesai.');
        }

        return self::SUCCESS;
    }
}
