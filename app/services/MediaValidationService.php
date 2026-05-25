<?php

namespace App\Services;

class MediaValidationService
{
    private const IMAGE_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
    private const VIDEO_MIME_TYPES = ['video/mp4', 'video/quicktime'];

    public function determineMediaTypeFromMime(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        if (in_array($mimeType, self::IMAGE_MIME_TYPES, true)) {
            return 'image';
        }

        if (in_array($mimeType, self::VIDEO_MIME_TYPES, true)) {
            return 'video';
        }

        throw new \RuntimeException('Format tidak didukung');
    }
}