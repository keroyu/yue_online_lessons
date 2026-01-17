<?php

namespace App\Services;

class VideoEmbedService
{
    /**
     * Parse a video URL and extract platform, video_id, and embed_url.
     *
     * @param string $url The video URL to parse
     * @return array|null Returns array with 'platform', 'video_id', 'embed_url' or null if invalid
     */
    public function parse(string $url): ?array
    {
        // Vimeo: https://vimeo.com/1032766965 or https://player.vimeo.com/video/1032766965
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return [
                'platform' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => "https://player.vimeo.com/video/{$matches[1]}",
            ];
        }

        // YouTube: https://www.youtube.com/watch?v=xxx or https://youtu.be/xxx or https://www.youtube.com/embed/xxx
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return [
                'platform' => 'youtube',
                'video_id' => $matches[1],
                'embed_url' => "https://www.youtube.com/embed/{$matches[1]}",
            ];
        }

        return null;
    }

    /**
     * Check if a URL is a valid video URL (Vimeo or YouTube).
     *
     * @param string $url The URL to validate
     * @return bool
     */
    public function isValid(string $url): bool
    {
        return $this->parse($url) !== null;
    }
}
