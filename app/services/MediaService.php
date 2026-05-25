<?php

namespace App\Services;

use App\Models\PostDetail;
use App\Models\SosialPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function __construct(
        private readonly MediaValidationService $mediaValidationService
    ) {
    }

    public function UploadMedia(Request $request, int $id, int $userId): PostDetail
    {
        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $jumlahMedia = $postingan->media()->count();

        return $this->storeMediaFile(
            $postingan->id,
            $request->file('media'),
            $jumlahMedia,
            $postingan->caption,
            $postingan->hashtags,
            $postingan->text_template,
            $postingan->template_text
        );
    }

    public function HapusMedia(int $id, int $mediaId, int $userId): void
    {
        $postingan = SosialPost::where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $media = PostDetail::where('id', $mediaId)
            ->where('post_id', $postingan->id)
            ->firstOrFail();

        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();
    }

    public function storeMediaFile(
        int $postId,
        $file,
        int $order = 0,
        ?string $caption = null,
        ?string $hashtags = null,
        ?string $textTemplate = null,
        ?string $templateText = null
    ): PostDetail {
        return $this->simpanMedia($postId, $file, $order, $caption, $hashtags, $textTemplate, $templateText);
    }

    private function simpanMedia(
        int $postId,
        $file,
        int $order = 0,
        ?string $caption = null,
        ?string $hashtags = null,
        ?string $textTemplate = null,
        ?string $templateText = null
    ): PostDetail {
        $post = SosialPost::findOrFail($postId);
        $mime = strtolower((string) $file->getMimeType());
        $tipe = $this->mediaValidationService->determineMediaTypeFromMime($mime);
        $extension = $file->getClientOriginalExtension();

        if (empty($extension)) {
            Log::warning('Media upload: Extension kosong, fallback ke mime type', [
                'post_id' => $postId,
                'mime_type' => $mime,
                'original_name' => $file->getClientOriginalName(),
            ]);
            // Fallback extension berdasar mime
            $extension = $this->extensionFromMime($mime);
        }

        $namaFile = Str::uuid() . '.' . $extension;
        $path = $file->storeAs("media/{$postId}", $namaFile, 'public');
        $fullUrl = Storage::url($path);

        Log::info('Media upload diagnostics', [
            'post_id' => $postId,
            'mime_type' => $mime,
            'media_type' => $tipe,
            'media_url' => $fullUrl,
        ]);

        Log::info('Media upload: Success', [
            'post_id' => $postId,
            'file_name' => $namaFile,
            'file_path' => $path,
            'file_url' => $fullUrl,
            'mime_type' => $mime,
            'media_type' => $tipe,
        ]);

        return PostDetail::create([
            'post_id' => $postId,
            'caption' => $caption ?? (string) $post->caption,
            'hashtags' => $hashtags ?? $post->hashtags,
            'text_template' => $textTemplate ?? $post->text_template,
            'template_text' => $templateText ?? $post->template_text,
            'media_type' => $tipe,
            'file_path' => $path,
            'file_url' => $fullUrl,
            'file_size' => $file->getSize(),
            'mime_type' => $mime,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'order' => $order,
        ]);
    }

    private function extensionFromMime(string $mime): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv',
        ];

        return $mimeMap[$mime] ?? (str_contains($mime, 'video') ? 'mp4' : 'jpg');
    }

}
