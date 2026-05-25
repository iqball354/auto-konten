<?php

namespace App\Services;

use App\Models\SosialAccount;
use App\Models\SosialPost;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacebookService
{
    private string $apiVersion = 'v25.0';

    private const LARGE_FILE_WARNING_BYTES = 52428800;

    private function httpClient(int $timeout = 30)
    {
        return Http::withOptions([
            'verify' => (bool) config('services.meta.verify_ssl', true),
        ])->timeout($timeout);
    }

    private function decryptAccessToken(string $encryptedToken): string
    {
        try {
            return decrypt($encryptedToken);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            throw new \RuntimeException('Access token Facebook tidak bisa didekripsi: ' . $e->getMessage());
        }
    }

    public function publish(SosialPost $post, ?SosialAccount $targetAccount = null): array
    {
        if (!$targetAccount) {
            Log::warning('FB publish: targetAccount null, fallback ke account query', [
                'post_id' => $post->id,
                'user_id' => $post->user_id,
            ]);
        }

        $account = $targetAccount ?: SosialAccount::where('user_id', $post->user_id)
            ->where('platform', 'facebook')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$account) {
            throw new \RuntimeException('Akun Facebook aktif tidak ditemukan.');
        }

        $pageId = $account->page_id ?: $account->platform_user_id;
        $accessToken = $this->decryptAccessToken((string) $account->access_token);
        $message = trim(($post->caption ?? '') . "\n" . ($post->hashtags ?? ''));
        $firstMedia = $post->media
            ->filter(fn ($media) => !empty($media->file_path))
            ->sortBy('order')
            ->first();

        if (!$pageId || trim($accessToken) === '') {
            throw new \RuntimeException('Data page_id atau access token akun Facebook tidak valid.');
        }

        if ($firstMedia) {
            $localPath = Storage::disk('public')->path($firstMedia->file_path);

            if (!is_file($localPath)) {
                throw new \RuntimeException("File media tidak ditemukan: {$firstMedia->file_path}");
            }

            $isVideo = ($firstMedia->media_type ?? null) === 'video'
                || str_starts_with((string) ($firstMedia->mime_type ?? ''), 'video/');

            return $isVideo
                ? $this->uploadVideo($post, $account, $localPath, $message)
                : $this->uploadPhoto($post, $account, $localPath, $message);
        }

        if (!empty($post->text_template)) {
            $templateText = trim((string) ($post->template_text ?? ''));
            if ($templateText === '') {
                $templateText = (string) ($post->caption ?? '');
            }

            $templateImage = $this->buildTextTemplateImage($templateText, $post->text_template);
            if (!empty($templateImage) && is_file($templateImage)) {
                try {
                    return $this->uploadPhoto($post, $account, $templateImage, $message);
                } finally {
                    @unlink($templateImage);
                }
            }

            Log::warning("FB template image fallback: render template gagal, kembali ke text feed post [{$post->id}].");
        }

        return $this->postText($post, $account, $message);
    }

    public function uploadPhoto(SosialPost $post, SosialAccount $account, string $localPath, string $message): array
    {
        $pageId = (string) ($account->page_id ?: $account->platform_user_id);
        $accessToken = $this->decryptAccessToken((string) $account->access_token);

        if ($pageId === '' || trim($accessToken) === '') {
            throw new \RuntimeException('Data page_id atau access token akun Facebook tidak valid.');
        }

        return $this->sendMultipartUpload(
            (int) $post->id,
            'photo',
            "https://graph.facebook.com/{$this->apiVersion}/{$pageId}/photos",
            $localPath,
            $message,
            60,
            [
                'message'      => $message,
                'access_token' => $accessToken,
            ]
        );
    }

    public function uploadVideo(SosialPost $post, SosialAccount $account, string $localPath, string $message): array
    {
        $pageId = (string) ($account->page_id ?: $account->platform_user_id);
        $accessToken = $this->decryptAccessToken((string) $account->access_token);

        if ($pageId === '' || trim($accessToken) === '') {
            throw new \RuntimeException('Data page_id atau access token akun Facebook tidak valid.');
        }

        return $this->sendMultipartUpload(
            (int) $post->id,
            'video',
            "https://graph.facebook.com/{$this->apiVersion}/{$pageId}/videos",
            $localPath,
            $message,
            180,
            [
                'description'  => $message,
                'access_token' => $accessToken,
            ]
        );
    }

    public function postText(SosialPost $post, SosialAccount $account, string $message): array
    {
        $pageId = (string) ($account->page_id ?: $account->platform_user_id);
        $accessToken = $this->decryptAccessToken((string) $account->access_token);

        if ($pageId === '' || trim($accessToken) === '') {
            throw new \RuntimeException('Data page_id atau access token akun Facebook tidak valid.');
        }

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$pageId}/feed";

        Log::info('FB text publish start', [
            'post_id' => $post->id,
            'page_id' => $pageId,
            'message_length' => strlen($message),
        ]);

        try {
            $response = $this->httpClient(60)
                ->retry(1, 2000, function ($exception, $response) {
                    if ($exception) {
                        return $exception instanceof ConnectionException || $exception instanceof \GuzzleHttp\Exception\ConnectException;
                    }

                    return $response && method_exists($response, 'serverError') && $response->serverError();
                })
                ->asForm()
                ->post($url, [
                    'message'      => $message,
                    'access_token' => $accessToken,
                ]);
        } catch (\Throwable $e) {
            Log::error('FB text publish request exception', [
                'post_id' => $post->id,
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('FB text publish failed', [
                'post_id' => $post->id,
                'page_id' => $pageId,
                'status' => $response->status(),
                'response_body' => $body,
            ]);

            throw new \RuntimeException('Gagal publish teks ke Facebook: ' . $body);
        }

        $result = $this->normalizePublishResult($response);

        Log::info('FB text publish success', [
            'post_id' => $post->id,
            'page_id' => $pageId,
            'platform_post_id' => $result['platform_post_id'],
        ]);

        return $result;
    }

    private function sendMultipartUpload(
        int $postId,
        string $uploadType,
        string $url,
        string $localPath,
        string $message,
        int $timeout,
        array $formData
    ): array {
        if (!is_file($localPath)) {
            throw new \RuntimeException("File media tidak ditemukan: {$localPath}");
        }

        $fileSize = @filesize($localPath);
        Log::info('FB upload start', [
            'post_id' => $postId,
            'upload_type' => $uploadType,
            'file_path' => $localPath,
            'file_size_bytes' => $fileSize,
            'message_length' => strlen($message),
            'timeout_seconds' => $timeout,
        ]);

        if (is_int($fileSize) && $fileSize > self::LARGE_FILE_WARNING_BYTES) {
            Log::warning('FB upload large file warning', [
                'post_id' => $postId,
                'upload_type' => $uploadType,
                'file_path' => $localPath,
                'file_size_bytes' => $fileSize,
            ]);
        }

        $stream = fopen($localPath, 'r');
        if ($stream === false) {
            throw new \RuntimeException("Tidak bisa membuka file: {$localPath}");
        }

        try {
            $response = $this->httpClient($timeout)
                ->retry(1, 2000, function ($exception, $response) {
                    if ($exception) {
                        return $exception instanceof ConnectionException || $exception instanceof \GuzzleHttp\Exception\ConnectException;
                    }

                    return $response && method_exists($response, 'serverError') && $response->serverError();
                })
                ->attach('source', $stream, basename($localPath))
                ->post($url, $formData);
        } catch (\Throwable $e) {
            Log::error('FB upload request exception', [
                'post_id' => $postId,
                'upload_type' => $uploadType,
                'file_path' => $localPath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            fclose($stream);
        }

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('FB upload failed', [
                'post_id' => $postId,
                'upload_type' => $uploadType,
                'file_path' => $localPath,
                'status' => $response->status(),
                'response_body' => $body,
            ]);

            throw new \RuntimeException('Gagal upload ' . $uploadType . ' ke Facebook: ' . $body);
        }

        $result = $this->normalizePublishResult($response);

        Log::info('FB upload success', [
            'post_id' => $postId,
            'upload_type' => $uploadType,
            'file_path' => $localPath,
            'platform_post_id' => $result['platform_post_id'],
        ]);

        return $result;
    }

    private function normalizePublishResult(Response $response): array
    {
        $json = $response->json();
        $payload = is_array($json) ? $json : ['raw' => $response->body()];

        return [
            'platform_post_id' => (string) ($payload['post_id'] ?? $payload['id'] ?? $payload['video_id'] ?? ''),
            'response_payload' => $payload,
        ];
    }

    private function buildTextTemplateImage(string $text, ?string $template): ?string
    {
        Log::info('FB buildTextTemplateImage start', ['template' => $template, 'text_length' => strlen($text)]);

        if (!extension_loaded('gd')) {
            Log::warning('FB buildTextTemplateImage: gd extension not loaded');
            return null;
        }

        $width = 1080;
        $height = 1080;

        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas) {
            Log::warning('FB buildTextTemplateImage: imagecreatetruecolor failed');
            return null;
        }

        $gradient = $this->templateGradient($template);
        $this->paintLinearGradient($canvas, $gradient[0], $gradient[1], $width, $height);

        $fontPath = $this->resolveFontPath();
        if (!$fontPath) {
            Log::warning('FB buildTextTemplateImage: fontPath not found');
            imagedestroy($canvas);
            return null;
        }

        $message = trim($text);
        if ($message === '') {
            $message = 'Postingan';
        }

        $fontSize = 52;
        $lineHeight = 72;
        $maxTextWidth = $width - 160;

        $lines = $this->wrapTextLines($message, $fontPath, $fontSize, $maxTextWidth);
        while (count($lines) > 9 && $fontSize > 30) {
            $fontSize -= 4;
            $lineHeight = (int) round($fontSize * 1.35);
            $lines = $this->wrapTextLines($message, $fontPath, $fontSize, $maxTextWidth);
        }

        if (count($lines) > 9) {
            $lines = array_slice($lines, 0, 9);
            $last = (string) end($lines);
            $lines[count($lines) - 1] = Str::limit($last, 42, '...');
        }

        $textColor = imagecolorallocate($canvas, 246, 250, 255);
        $shadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, 85);

        $totalHeight = max(1, count($lines)) * $lineHeight;
        $startY = (int) (($height - $totalHeight) / 2) + $fontSize;

        foreach ($lines as $index => $line) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $line);
            $lineWidth = abs($bbox[2] - $bbox[0]);
            $x = (int) (($width - $lineWidth) / 2);
            $y = $startY + ($index * $lineHeight);

            imagettftext($canvas, $fontSize, 0, $x + 2, $y + 2, $shadowColor, $fontPath, $line);
            imagettftext($canvas, $fontSize, 0, $x, $y, $textColor, $fontPath, $line);
        }

        $tempFile = storage_path('app/temp/template_' . Str::uuid() . '.png');
        $tempDir = dirname($tempFile);
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $written = imagepng($canvas, $tempFile, 6);
        imagedestroy($canvas);

        if ($written && is_file($tempFile)) {
            Log::info('FB buildTextTemplateImage: template image written', ['temp_file' => $tempFile]);
            return $tempFile;
        }

        Log::error('FB buildTextTemplateImage: failed to write template image', ['temp_file' => $tempFile, 'written' => $written]);
        return null;
    }

    private function templateGradient(?string $template): array
    {
        $map = [
            'classic_aurora' => [[61, 123, 255], [232, 65, 141]],
            'sunset_fade' => [[249, 115, 22], [139, 92, 246]],
            'royal_plum' => [[124, 58, 237], [219, 39, 119]],
            'emerald_wave' => [[5, 150, 105], [14, 165, 233]],
            'midnight_blue' => [[15, 23, 42], [30, 58, 138]],
            'orange_pop' => [[251, 146, 60], [244, 63, 94]],
            'mono_ink' => [[17, 24, 39], [55, 65, 81]],
            'neon_blend' => [[6, 182, 212], [244, 63, 94]],
        ];

        return $map[$template ?? ''] ?? [[23, 35, 56], [40, 61, 99]];
    }

    private function paintLinearGradient($image, array $from, array $to, int $width, int $height): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) round($from[0] + (($to[0] - $from[0]) * $ratio));
            $g = (int) round($from[1] + (($to[1] - $from[1]) * $ratio));
            $b = (int) round($from[2] + (($to[2] - $from[2]) * $ratio));
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }

    private function resolveFontPath(): ?string
    {
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            base_path('resources/fonts/OpenSans-Bold.ttf'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        Log::warning('FB resolveFontPath: font candidates tidak ditemukan', [
            'candidates' => $candidates,
        ]);

        return null;
    }

    private function wrapTextLines(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        if (empty($words)) {
            return [''];
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $test);
            $lineWidth = abs($bbox[2] - $bbox[0]);

            if ($lineWidth <= $maxWidth || $current === '') {
                $current = $test;
                continue;
            }

            $lines[] = $current;
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
