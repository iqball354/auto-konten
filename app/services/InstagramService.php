<?php

namespace App\Services;

use App\Models\SosialAccount;
use App\Models\SosialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private string $apiVersion = 'v25.0';

    public function __construct(
        private readonly MediaValidationService $mediaValidationService,
        private readonly \App\Services\MetaService $metaService
    ) {
    }

    private function resolvePollingConfig(bool $isVideo): array
    {
        return $isVideo
            ? ['max_attempts' => 15, 'sleep_seconds' => 5]  // max 75 detik
            : ['max_attempts' => 5, 'sleep_seconds' => 2];  // max 10 detik
    }

    public function createMediaContainer(string $igUserId, string $accessToken, string $mediaUrl, string $caption, bool $isVideo): string
    {
        $createPayload = [
            'caption' => $caption,
        ];

        if ($isVideo) {
            $createPayload['video_url'] = $mediaUrl;
            $createPayload['media_type'] = 'REELS';
        } else {
            $createPayload['image_url'] = $mediaUrl;
        }

        $createPayload['access_token'] = $accessToken;

        $createRes = Http::timeout(30)
            ->retry(3, 1000)
            ->asForm()
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", $createPayload);

        if ($createRes->failed() || !$createRes->json('id')) {
            $errorMsg = $createRes->json('error.message') ?? $createRes->body();
            Log::error('IG publish: container creation failed', [
                'response_code' => $createRes->status(),
                'error' => $errorMsg,
                'payload' => [
                    'media_type' => $createPayload['media_type'] ?? null,
                    'video_url' => isset($createPayload['video_url']) ? substr($createPayload['video_url'], 0, 100) : null,
                    'image_url' => isset($createPayload['image_url']) ? substr($createPayload['image_url'], 0, 100) : null,
                ],
            ]);
            throw new \RuntimeException('Gagal membuat container Instagram: ' . $errorMsg);
        }

        return (string) $createRes->json('id');
    }

    public function publishContainer(string $igUserId, string $accessToken, string $creationId): array
    {
        $publishRes = Http::timeout(30)
            ->retry(3, 1000)
            ->asForm()
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media_publish", [
                'creation_id'  => $creationId,
                'access_token' => $accessToken,
            ]);

        if ($publishRes->failed()) {
            throw new \RuntimeException(
                'Gagal publish Instagram: ' . ($publishRes->json('error.message') ?? $publishRes->body())
            );
        }

        return [
            'platform_post_id' => (string) ($publishRes->json('id') ?? $creationId),
            'response_payload' => is_array($publishRes->json()) ? $publishRes->json() : ['raw' => $publishRes->body()],
        ];
    }

    public function publish(SosialPost $post, SosialAccount $targetAccount): array
    {
        $igUserId = (string) ($targetAccount->platform_user_id ?? '');
        if ($igUserId === '') {
            throw new \RuntimeException('ID akun Instagram tidak tersedia di sosial account.');
        }

        try {
            $accessToken = decrypt((string) $targetAccount->access_token);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            throw new \RuntimeException('Access token Instagram tidak bisa didekripsi: ' . $e->getMessage());
        }

        if (trim($accessToken) === '') {
            throw new \RuntimeException('Access token Instagram tidak valid.');
        }

        // Ensure IG account is connected to a Facebook Page (required for publishing)
        if (empty($targetAccount->page_id)) {
            throw new \RuntimeException('Akun Instagram tidak terhubung ke Facebook Page. Silakan hubungkan ulang akun.');
        }

        // Check that token scopes contain publish permission
        try {
            $tokenStatus = $this->metaService->checkTokenStatus($targetAccount);
            $granted = array_map('strval', (array) ($tokenStatus['scopes'] ?? []));
            if (!in_array('instagram_content_publish', $granted, true)) {
                throw new \RuntimeException('Aplikasi tidak memiliki izin instagram_content_publish pada token ini.');
            }
        } catch (\Throwable $e) {
            Log::warning('IG publish: token scope check failed or missing publish scope', ['post_id' => $post->id, 'error' => $e->getMessage()]);
        }

        $message = trim((string) (($post->caption ?? '') . "\n" . ($post->hashtags ?? '')));
        $firstMedia = $post->media
            ->filter(fn ($media) => !empty($media->file_url))
            ->sortBy('order')
            ->values();

        if ($firstMedia->isEmpty()) {
            throw new \RuntimeException('Instagram publish butuh media. Post tanpa media belum didukung untuk endpoint ini.');
        }

        // Support carousel if multiple media
        $mediaItems = $firstMedia;
        $first = $mediaItems->first();
        $mediaUrl = trim((string) ($first->file_url ?? ''));
        if ($mediaUrl === '') {
            throw new \RuntimeException('URL media Instagram tidak tersedia.');
        }

        if (!str_starts_with(strtolower($mediaUrl), 'https://')) {
            throw new \RuntimeException('Media URL harus menggunakan https');
        }

        $mime = strtolower((string) ($first->mime_type ?? ''));
        $mediaType = $this->mediaValidationService->determineMediaTypeFromMime($mime);
        $isVideo = $mediaType === 'video';

        Log::info('IG media diagnostics', [
            'post_id' => $post->id,
            'mime_type' => $mime,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ]);

        // If multiple media => create child containers and then a carousel parent
        if ($mediaItems->count() > 1) {
            $children = [];
            foreach ($mediaItems as $item) {
                $url = trim((string) ($item->file_url ?? ''));
                if ($url === '' || ! $this->isUrlReachable($url)) {
                    throw new \RuntimeException('Salah satu media tidak dapat diakses oleh Facebook: ' . $url);
                }
                $mime = strtolower((string) ($item->mime_type ?? ''));
                $type = $this->mediaValidationService->determineMediaTypeFromMime($mime);
                $children[] = $this->createMediaContainer($igUserId, $accessToken, $url, '', $type === 'video');
            }

            $parentPayload = [
                'caption' => $message,
                'children' => implode(',', $children),
                'media_type' => 'CAROUSEL',
                'access_token' => $accessToken,
            ];

            $parentRes = Http::timeout(30)->retry(3, 1000)->asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", $parentPayload);
            if ($parentRes->failed() || !$parentRes->json('id')) {
                throw new \RuntimeException('Gagal membuat carousel container: ' . ($parentRes->json('error.message') ?? $parentRes->body()));
            }

            $creationId = (string) $parentRes->json('id');
            $pollingConfig = $this->resolvePollingConfig(false);
            $this->waitUntilContainerReady($creationId, $accessToken, $post->id, $pollingConfig['sleep_seconds'], $pollingConfig['max_attempts']);
            $publishResult = $this->publishContainer($igUserId, $accessToken, $creationId);
        } else {
            if (! $this->isUrlReachable($mediaUrl)) {
                throw new \RuntimeException('Media URL tidak dapat diakses oleh Facebook: ' . $mediaUrl);
            }

            $creationId = $this->createMediaContainer($igUserId, $accessToken, $mediaUrl, $message, $isVideo);

            Log::info('IG publish: container created, waiting for ready', [
                'post_id' => $post->id,
                'creation_id' => $creationId,
            ]);

            $pollingConfig = $this->resolvePollingConfig($isVideo);
            $this->waitUntilContainerReady($creationId, $accessToken, $post->id, $pollingConfig['sleep_seconds'], $pollingConfig['max_attempts']);

            $publishResult = $this->publishContainer($igUserId, $accessToken, $creationId);
        }

        Log::info('IG publish: success', [
            'post_id' => $post->id,
            'platform_post_id' => $publishResult['platform_post_id'] ?? $creationId,
        ]);

        return $publishResult;
    }

    public function waitUntilContainerReady(string $creationId, string $accessToken, int $postId, int $sleepSeconds, int $maxAttempts): void
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $statusRes = Http::get(
                "https://graph.facebook.com/{$this->apiVersion}/{$creationId}",
                [
                    'fields' => 'status_code,status',
                    'access_token' => $accessToken,
                ]
            );

            if ($statusRes->failed()) {
                $errorMsg = $statusRes->json('error.message') ?? $statusRes->body();
                Log::warning('IG publish: status check failed (attempt ' . $attempt . ')', [
                    'post_id' => $postId,
                    'creation_id' => $creationId,
                    'http_status' => $statusRes->status(),
                    'error' => $errorMsg,
                ]);

                // If status endpoint fails transiently, continue trying.
                if ($attempt < $maxAttempts) {
                    sleep($sleepSeconds);
                    continue;
                }

                throw new \RuntimeException(
                    'Gagal cek status container Instagram: ' . $errorMsg
                );
            }

            $status = strtoupper((string) ($statusRes->json('status_code') ?? $statusRes->json('status') ?? ''));

            Log::debug('IG publish: status check', [
                'post_id' => $postId,
                'creation_id' => $creationId,
                'attempt' => $attempt,
                'status' => $status,
            ]);

            if (in_array($status, ['FINISHED', 'PUBLISHED'], true)) {
                Log::info('IG publish: container ready', [
                    'post_id' => $postId,
                    'creation_id' => $creationId,
                    'attempts' => $attempt,
                ]);
                return;
            }

            if (in_array($status, ['ERROR', 'FAILED', 'EXPIRED'], true)) {
                Log::error('IG publish: container processing error', [
                    'post_id' => $postId,
                    'creation_id' => $creationId,
                    'status' => $status,
                    'full_response' => $statusRes->json(),
                ]);
                throw new \RuntimeException(
                    'Container Instagram gagal diproses dengan status: ' . $status
                );
            }

            if ($attempt < $maxAttempts) {
                sleep($sleepSeconds);
            }
        }

        Log::warning('IG publish: polling timeout', [
            'post_id' => $postId,
            'creation_id' => $creationId,
            'max_attempts' => $maxAttempts,
        ]);

        throw new \RuntimeException("Container [{$creationId}] tidak siap setelah {$maxAttempts} percobaan.");
    }

}