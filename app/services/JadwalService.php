<?php

namespace App\Services;

use App\Models\PostDetail;
use App\Models\PostScheduler;
use App\Models\SosialAccount;
use App\Models\SosialPost;
use App\Repositories\PostRepository;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class JadwalService
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly PostRepository $postRepository
    )
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarEvents(int $userId): array
    {
        $jadwal = $this->postRepository->getCalendarSchedules($userId);

        $postIds = $jadwal->pluck('detail.post.id')->unique()->all();
        $postLogs = $this->postRepository->getLatestSuccessLogs($postIds);

        return $jadwal->map(function (PostScheduler $item) use ($postLogs): array {
            $post = $item->detail?->post;
            $url = '#';

            if ($post && $post->status === 'published' && isset($postLogs[$post->id])) {
                $postLog = $postLogs[$post->id];

                if (!empty($postLog->platform_post_id)) {
                    $platforms = is_array($post->platform_targets) ? $post->platform_targets : [];
                    $platform = !empty($platforms) ? (string) reset($platforms) : 'facebook';

                    $url = $platform === 'instagram'
                        ? "https://www.instagram.com/p/{$postLog->platform_post_id}/"
                        : "https://www.facebook.com/{$postLog->platform_post_id}/";
                }
            }

            return [
                'id' => $item->id,
                'title' => Str::limit($post->caption ?? '-', 40),
                'start' => $item->scheduled_at,
                'status' => $item->status,
                'platform' => $item->akunSosial->platform ?? '-',
                'username' => $item->akunSosial->username ?? '-',
                'url' => $url,
            ];
        })->all();
    }

    public function StoreJadwal(Request $request, int $userId): void
    {
        $caption = (string) $request->caption;
        $hashtags = $request->hashtags;
        $textTemplate = $this->normalizeTextTemplate($request->input('text_template'), $request->hasFile('media'));
        $templateText = $this->normalizeTemplateText($request->input('template_text'), $textTemplate, $caption);
        $flowEnabled = $request->boolean('flow_enabled');
        $flowDays = max(2, min(60, (int) $request->input('flow_days', 7)));
        $flowIntervalDays = max(1, min(30, (int) $request->input('flow_interval_days', 1)));

        $akun = SosialAccount::where('id', $request->social_account_id)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->firstOrFail();

        $postingan = SosialPost::create([
            'user_id' => $userId,
            'status' => 'scheduled',
            'publish_type' => 'scheduled',
            'platform_targets' => $request->platforms,
        ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $this->mediaService->storeMediaFile($postingan->id, $file, $index, $caption, $hashtags, $textTemplate, $templateText);
            }
        }

        $this->ensureDetailExists($postingan, $caption, $hashtags, $textTemplate, $templateText);

        if ($flowEnabled) {
            $this->createFlowSchedulersFromPost(
                $postingan,
                (int) $akun->id,
                (string) $request->scheduled_at,
                $flowDays,
                $flowIntervalDays
            );
            return;
        }

        $this->createSchedulerFromPost($postingan, (int) $akun->id, $request->scheduled_at);
    }

    public function UpdateJadwal(Request $request, int $id, int $userId): void
    {
        $jadwal = PostScheduler::whereHas('detail.post', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('id', $id)
            ->firstOrFail();

        if ($jadwal->status !== 'pending') {
            throw new \RuntimeException('Jadwal yang sudah diproses tidak bisa diubah.');
        }

        $jadwal->update([
            'scheduled_at' => $request->scheduled_at,
            'sosial_account_id' => $request->social_account_id ?? $jadwal->sosial_account_id,
        ]);
    }

    public function BatalJadwal(int $id, int $userId): void
    {
        $jadwal = PostScheduler::whereHas('detail.post', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('id', $id)
            ->firstOrFail();

        if ($jadwal->status !== 'pending') {
            throw new \RuntimeException('Jadwal yang sudah diproses tidak bisa dibatalkan.');
        }

        $postId = $jadwal->detail?->post_id;
        $jadwal->delete();

        $sisaPending = PostScheduler::whereHas('detail', function ($q) use ($postId) {
                $q->where('post_id', $postId);
            })
            ->where('status', 'pending')
            ->count();

        if ($postId && $sisaPending === 0) {
            SosialPost::where('id', $postId)->update(['status' => 'draft']);
        }
    }

    private function createSchedulerFromPost(SosialPost $post, int $accountId, $scheduledAt): PostScheduler
    {
        $this->ensureDetailExists($post, $post->caption, $post->hashtags, $post->text_template, $post->template_text);

        $detail = PostDetail::where('post_id', $post->id)
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (!$detail) {
            throw new \RuntimeException('Detail postingan belum tersedia di post_detail.');
        }

        return PostScheduler::create([
            'detail_id' => $detail->id,
            'sosial_account_id' => $accountId,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
            'retry_count' => 0,
        ]);
    }

    private function createFlowSchedulersFromPost(
        SosialPost $post,
        int $accountId,
        string $baseScheduledAt,
        int $flowDays,
        int $flowIntervalDays
    ): void {
        $base = \Carbon\Carbon::parse($baseScheduledAt);

        for ($i = 0; $i < $flowDays; $i++) {
            $scheduledAt = $base->copy()->addDays($i * $flowIntervalDays);
            $this->createSchedulerFromPost($post, $accountId, $scheduledAt);
        }
    }

    private function ensureDetailExists(
        SosialPost $post,
        ?string $caption = null,
        ?string $hashtags = null,
        ?string $textTemplate = null,
        ?string $templateText = null
    ): void {
        $exists = PostDetail::where('post_id', $post->id)->exists();
        if ($exists) {
            return;
        }

        PostDetail::create([
            'post_id' => $post->id,
            'caption' => $caption ?? (string) $post->caption,
            'hashtags' => $hashtags ?? $post->hashtags,
            'text_template' => $textTemplate ?? $post->text_template,
            'template_text' => $templateText ?? $post->template_text,
            'media_type' => null,
            'file_path' => null,
            'file_url' => null,
            'file_size' => null,
            'mime_type' => null,
            'checksum' => $this->buildDetailChecksum(
                $caption ?? (string) $post->caption,
                $hashtags ?? $post->hashtags,
                $textTemplate ?? $post->text_template,
                $templateText ?? $post->template_text
            ),
            'order' => 0,
        ]);
    }

    private function buildDetailChecksum(?string $caption, ?string $hashtags, ?string $textTemplate, ?string $templateText): string
    {
        return hash('sha256', json_encode([
            'caption' => $caption,
            'hashtags' => $hashtags,
            'text_template' => $textTemplate,
            'template_text' => $templateText,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeTextTemplate(?string $template, bool $hasMedia): ?string
    {
        if ($hasMedia) {
            return null;
        }

        $allowed = [
            'classic_aurora',
            'sunset_fade',
            'royal_plum',
            'emerald_wave',
            'midnight_blue',
            'orange_pop',
            'mono_ink',
            'neon_blend',
        ];

        $value = trim((string) $template);

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function normalizeTemplateText(?string $templateText, ?string $textTemplate, string $caption): ?string
    {
        if (empty($textTemplate)) {
            return null;
        }

        $value = trim((string) $templateText);
        if ($value === '') {
            $value = trim($caption);
        }

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 220);
    }
}
