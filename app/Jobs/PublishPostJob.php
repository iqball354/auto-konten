<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PostLog;
use App\Models\PostScheduler;
use App\Models\SosialAccount;
use App\Models\SosialPost;
use App\Repositories\PostRepository;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function backoff(): array
    {
        return [5, 10, 15];
    }

    public function __construct(
        protected int $postId,
        protected ?int $schedulerId = null,
        protected ?int $accountId = null,
    ) {
    }

    public function handle(PostRepository $postRepository, FacebookService $facebookService, InstagramService $instagramService): void
    {
        Log::info('PublishPostJob: start', [
            'post_id' => $this->postId,
            'scheduler_id' => $this->schedulerId,
            'account_id' => $this->accountId,
            'attempt' => $this->attempts(),
        ]);

        if (PostLog::where('post_id', $this->postId)->where('status', 'success')->exists()) {
            Log::info('PublishPostJob: already published, skip.', ['post_id' => $this->postId]);
            return;
        }

        $post = SosialPost::whereNull('deleted_at')->with('media')->find($this->postId);
        if (!$post) {
            Log::info('PublishPostJob: post tidak ditemukan atau sudah dihapus, skip.', [
                'post_id' => $this->postId,
            ]);
            return;
        }

        if ($post->status === 'published') {
            Log::info('PublishPostJob: post sudah published, skip.', ['post_id' => $this->postId]);
            return;
        }

        $scheduler = $this->schedulerId
            ? PostScheduler::with(['akunSosial', 'detail.post'])->find($this->schedulerId)
            : null;

        $platform = $this->resolvePlatform($post, $scheduler);
        $account = $this->resolveTargetAccount($post, $scheduler, $platform);

        if (!$account) {
            throw new \RuntimeException("Akun {$platform} aktif tidak ditemukan.");
        }

        Log::info('PublishPostJob: publish direct', [
            'post_id' => $this->postId,
            'platform' => $platform,
            'scheduler_id' => $this->schedulerId,
            'account_id' => $account->id,
        ]);

        $result = match ($platform) {
            'facebook' => $facebookService->publish($post, $account),
            'instagram' => $instagramService->publish($post, $account),
            default => throw new \RuntimeException("Platform tidak dikenal: {$platform}"),
        };

        if (empty($result['platform_post_id'])) {
            throw new \RuntimeException('Response publish tidak mengembalikan platform_post_id yang valid.');
        }

        $this->finalizePublishedPost(
            $post,
            $scheduler,
            $account,
            $platform,
            (string) $result['platform_post_id'],
            (array) ($result['response_payload'] ?? []),
            $postRepository
        );
    }

    public function failed(\Throwable $e): void
    {
        $post = SosialPost::whereKey($this->postId)->first();

        if ($post) {
            $post->update(['status' => 'failed']);

            Notification::create([
                'user_id' => (int) $post->user_id,
                'type' => 'posting_failed',
                'title' => 'Postingan gagal dipublish',
                'message' => 'Konten gagal dipublish: ' . $e->getMessage(),
                'data' => ['post_id' => (int) $post->id],
            ]);
        }

        if ($this->schedulerId) {
            PostScheduler::whereKey($this->schedulerId)->update([
                'status' => 'failed',
                'executed_at' => now(),
            ]);
        }

        PostLog::create([
            'post_id' => $this->postId,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'executed_at' => now(),
        ]);

        Log::error('PublishPostJob: failed', [
            'post_id' => $this->postId,
            'scheduler_id' => $this->schedulerId,
            'error' => $e->getMessage(),
        ]);

        app(PostRepository::class)->invalidateCache($this->postId);
    }

    private function resolvePlatform(SosialPost $post, ?PostScheduler $scheduler): string
    {
        if ($this->accountId) {
            $platform = SosialAccount::whereKey($this->accountId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->value('platform');

            if ($platform) {
                return strtolower((string) $platform);
            }
        }

        if ($scheduler?->akunSosial?->platform) {
            return strtolower((string) $scheduler->akunSosial->platform);
        }

        $targets = (array) ($post->platform_targets ?? []);
        foreach ($targets as $target) {
            $value = strtolower((string) $target);
            if (in_array($value, ['facebook', 'instagram'], true)) {
                return $value;
            }
        }

        throw new \RuntimeException(
            "Tidak bisa menentukan platform untuk post [{$post->id}]. Pastikan accountId, schedulerId, atau platform_targets diisi."
        );
    }

    private function resolveTargetAccount(SosialPost $post, ?PostScheduler $scheduler, string $platform): ?SosialAccount
    {
        if ($this->accountId) {
            $account = SosialAccount::whereKey($this->accountId)
                ->where('platform', $platform)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->first();

            if ($account) {
                return $account;
            }
        }

        if ($scheduler?->akunSosial) {
            $schedulerAccount = $scheduler->akunSosial;
            if ($schedulerAccount->platform === $platform && (int) $schedulerAccount->is_active === 1) {
                return $schedulerAccount;
            }
        }

        return SosialAccount::where('user_id', $post->user_id)
            ->where('platform', $platform)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();
    }

    private function finalizePublishedPost(
        SosialPost $post,
        ?PostScheduler $scheduler,
        SosialAccount $account,
        string $platform,
        string $platformPostId,
        array $responsePayload,
        PostRepository $postRepository
    ): void {
        $post->update(['status' => 'published']);

        if ($scheduler) {
            $scheduler->update([
                'status' => 'done',
                'executed_at' => now(),
            ]);
        }

        PostLog::create([
            'post_id' => $post->id,
            'status' => 'success',
            'platform_post_id' => $platformPostId,
            'response_payload' => $responsePayload,
            'executed_at' => now(),
        ]);

        Notification::create([
            'user_id' => (int) $post->user_id,
            'type' => 'posting_success',
            'title' => 'Postingan berhasil dipublish',
            'message' => 'Konten Anda berhasil dipublish ke ' . ucfirst($platform) . '.',
            'data' => ['post_id' => (int) $post->id],
        ]);

        $postRepository->invalidateCache($this->postId);

        $this->deletePostMedia($post);

        Log::info('PublishPostJob: success', [
            'post_id' => $post->id,
            'scheduler_id' => $this->schedulerId,
            'platform' => $platform,
            'platform_post_id' => $platformPostId,
            'account_id' => $account->id,
        ]);
    }

    private function deletePostMedia(SosialPost $post): void
    {
        if (!$post->media || $post->media->isEmpty()) {
            Log::info('PublishPostJob: no media to delete', ['post_id' => $post->id]);
            return;
        }

        Log::info('PublishPostJob: deleting media', [
            'post_id' => $post->id,
            'media_count' => $post->media->count(),
        ]);

        foreach ($post->media as $media) {
            try {
                $filePath = (string) ($media->file_path ?? '');
                if ($filePath === '') {
                    Log::debug('PublishPostJob: media file_path kosong', [
                        'post_id' => $post->id,
                        'media_id' => $media->id,
                    ]);
                    continue;
                }

                $exists = Storage::disk('public')->exists($filePath);
                Log::debug('PublishPostJob: checking media file', [
                    'post_id' => $post->id,
                    'media_id' => $media->id,
                    'file_path' => $filePath,
                    'exists' => $exists,
                ]);

                if ($exists) {
                    Storage::disk('public')->delete($filePath);
                    Log::info('PublishPostJob: media file deleted', [
                        'post_id' => $post->id,
                        'media_id' => $media->id,
                        'file_path' => $filePath,
                    ]);
                }

                $media->update(['file_path' => null, 'file_url' => null]);
            } catch (\Throwable $deleteError) {
                Log::warning('PublishPostJob: delete media gagal', [
                    'post_id' => $post->id,
                    'media_id' => $media->id,
                    'error' => $deleteError->getMessage(),
                ]);
            }
        }
    }
}