<?php

namespace App\Services;

use App\Jobs\PublishPostJob;
use App\Models\PostLog;
use App\Models\PostDetail;
use App\Models\PostScheduler;
use App\Models\SosialAccount;
use App\Models\SosialPost;
use App\Repositories\PostRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PostingService
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly PostRepository $postRepository,
        private readonly AkunTerhubungService $akunTerhubungService
    ) {
    }

    /**
     * @return array{postingan: LengthAwarePaginator, publishErrors: array<int, mixed>, postLogs: array<int, PostLog>, akun_list: Collection<int, SosialAccount>}
     */
    public function getPostingPageData(int $userId, array $filters): array
    {
        $postingan = $this->postRepository->getPaginatedPostsWithInfo($userId, $filters, 10);
        $postIds = $postingan->getCollection()->pluck('id')->all();

        return [
            'postingan' => $postingan,
            'publishErrors' => $this->postRepository->getPostsErrors($postIds),
            'postLogs' => $this->getLatestSuccessLogs($postIds),
            'akun_list' => $this->akunTerhubungService->getActiveAccounts($userId),
        ];
    }

    /**
     * @return array<int, PostLog>
     */
    public function getLatestSuccessLogs(array $postIds): array
    {
        return $this->postRepository->getLatestSuccessLogs($postIds);
    }

    public function getRiwayatData(int $userId, array $filters): LengthAwarePaginator
    {
        return $this->postRepository->getRiwayatData($userId, $filters);
    }

    public function getLogDetail(int $userId, int $logId): PostLog
    {
        return $this->postRepository->getLogDetail($userId, $logId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPostInfoOptimized(int $postId): ?array
    {
        return $this->postRepository->getPostInfo($postId, true);
    }

    public function StorePosting(Request $request, int $userId): string
    {
        $submitMode = $request->input('submit_mode', 'publish');
        $isDraftOnly = $submitMode === 'draft';

        $caption = (string) $request->caption;
        $hashtags = $request->hashtags;
        $textTemplate = $this->normalizeTextTemplate($request->input('text_template'), $request->hasFile('media'));
        $templateText = $this->normalizeTemplateText($request->input('template_text'), $textTemplate, $caption);

        $postingan = SosialPost::create([
            'user_id' => $userId,
            'status' => $isDraftOnly ? 'draft' : 'scheduled',
            'publish_type' => 'immediate',
            'platform_targets' => $request->platforms,
        ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $this->mediaService->storeMediaFile($postingan->id, $file, $index, $caption, $hashtags, $textTemplate, $templateText);
            }
        }

        $this->ensureDetailExists($postingan, $caption, $hashtags, $textTemplate, $templateText);

        if (!$isDraftOnly) {
            $targetPlatform = $this->resolvePrimaryPlatform((array) $request->platforms);
            $targetAccount = $this->resolveDefaultActiveAccount($userId, $targetPlatform);
            if (!$targetAccount) {
                throw new \RuntimeException('Akun ' . ucfirst($targetPlatform) . ' aktif tidak ditemukan. Hubungkan akun terlebih dahulu.');
            }

            $scheduler = $this->createSchedulerFromPost($postingan, (int) $targetAccount->id, now());
            PublishPostJob::dispatchSync($postingan->id, $scheduler->id);
            $postingan->refresh();
        }

        // Invalidate cache after creating post
        $this->invalidatePostCache($postingan->id);

        return $isDraftOnly
            ? 'Draft berhasil disimpan.'
            : ($postingan->status === 'published'
                ? 'Postingan berhasil dipublish sekarang.'
                : 'Postingan dibuat, namun status publish belum final.');
    }

    public function UpdatePosting(Request $request, int $id, int $userId): void
    {
        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if (!in_array($postingan->status, ['draft', 'scheduled'])) {
            throw new \RuntimeException('Konten yang sudah dipublish atau gagal tidak bisa diedit.');
        }

        $caption = (string) $request->caption;
        $hashtags = $request->hashtags;
        $textTemplate = $this->normalizeTextTemplate($request->input('text_template'), $postingan->media()->exists());
        $templateText = $this->normalizeTemplateText($request->input('template_text'), $textTemplate, $caption);

        $postingan->update([
            'platform_targets' => $request->platforms,
        ]);

        $this->syncPostDetailText($postingan, $caption, $hashtags, $textTemplate, $templateText);
        $this->ensureDetailExists($postingan, $caption, $hashtags, $textTemplate, $templateText);

        // Invalidate cache after updating post
        $this->invalidatePostCache($postingan->id);
    }

    public function PublishNow(int $id, int $userId): bool
    {
        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        if ($postingan->status === 'published') {
            throw new \RuntimeException('Konten ini sudah dipublish sebelumnya.');
        }

        $postingan->update([
            'status' => 'scheduled',
            'publish_type' => 'immediate',
        ]);

        $targetPlatform = $this->resolvePrimaryPlatform((array) ($postingan->platform_targets ?? []));
        $targetAccount = $this->resolveDefaultActiveAccount($userId, $targetPlatform);
        if (!$targetAccount) {
            throw new \RuntimeException('Akun ' . ucfirst($targetPlatform) . ' aktif tidak ditemukan. Hubungkan akun terlebih dahulu.');
        }

        $postInfo = $this->postRepository->getPostInfo($postingan->id, true) ?? [];
        $scheduler = $this->createSchedulerFromPost(
            $postingan,
            (int) $targetAccount->id,
            now(),
            $this->resolveDetailIdFromInfo($postInfo)
        );
        PublishPostJob::dispatchSync($postingan->id, $scheduler->id);
        $postingan->refresh();

        // Invalidate cache after publishing
        $this->invalidatePostCache($postingan->id);

        return $postingan->status === 'published';
    }

    public function HapusPosting(int $id, int $userId): void
    {
        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $postingan->update(['deleted_at' => now()]);

        // Invalidate cache after deleting post
        $this->invalidatePostCache($postingan->id);
    }

    private function createSchedulerFromPost(SosialPost $post, int $accountId, $scheduledAt, ?int $detailId = null): PostScheduler
    {
        $this->ensureDetailExists($post, $post->caption, $post->hashtags, $post->text_template, $post->template_text);

        $detail = $detailId ? PostDetail::find($detailId) : null;

        if (!$detail) {
            $detail = PostDetail::where('post_id', $post->id)
                ->orderBy('order')
                ->orderBy('id')
                ->first();
        }

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

    private function resolveDetailIdFromInfo(array $postInfo): ?int
    {
        if (!empty($postInfo['detail']['id'])) {
            return (int) $postInfo['detail']['id'];
        }

        if (!empty($postInfo['detail_id'])) {
            return (int) $postInfo['detail_id'];
        }

        $firstMedia = $postInfo['media'][0] ?? null;
        if (is_array($firstMedia) && !empty($firstMedia['id'])) {
            return (int) $firstMedia['id'];
        }

        return null;
    }

    private function syncPostDetailText(
        SosialPost $post,
        string $caption,
        ?string $hashtags,
        ?string $textTemplate,
        ?string $templateText
    ): void
    {
        PostDetail::where('post_id', $post->id)->update([
            'caption' => $caption,
            'hashtags' => $hashtags,
            'text_template' => $textTemplate,
            'template_text' => $templateText,
            'checksum' => $this->buildDetailChecksum($caption, $hashtags, $textTemplate, $templateText),
        ]);
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

    private function resolvePrimaryPlatform(array $platformTargets): string
    {
        $allowed = ['instagram', 'facebook'];

        foreach ($platformTargets as $platform) {
            $value = strtolower((string) $platform);
            if (in_array($value, $allowed, true)) {
                return $value;
            }
        }

        return 'facebook';
    }

    private function resolveDefaultActiveAccount(int $userId, string $platform): ?SosialAccount
    {
        return SosialAccount::where('user_id', $userId)
            ->where('platform', $platform)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();
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

    /**
     * Get multiple posts info dengan batch processing optimized
     */
    public function getMultiplePostsInfoOptimized(array $postIds)
    {
        return $this->postRepository->getMultiplePostsInfo($postIds, useCache: true);
    }

    /**
     * Get paginated posts dengan full info untuk dashboard
     * Lebih cepat karena menggunakan getPostInfo stored procedure
     */
    public function getPaginatedPostsOptimized(int $userId, array $filters = [], int $perPage = 10)
    {
        return $this->postRepository->getPaginatedPostsWithInfo($userId, $filters, $perPage);
    }

    /**
     * Invalidate cache setelah post di-update
     */
    public function invalidatePostCache(int $postId)
    {
        $this->postRepository->invalidateCache($postId);
    }
}