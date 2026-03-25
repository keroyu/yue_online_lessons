<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlogRssService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const ARTICLE_COUNT = 5;
    private const TIMEOUT_SECONDS = 5;

    /**
     * Get the latest articles from the given RSS feed URL.
     *
     * @return array<int, array{title: string, url: string, published_at: string}>
     */
    public function getArticles(string $rssUrl): array
    {
        if (empty($rssUrl)) {
            return [];
        }

        $cacheKey = 'blog_articles_' . md5($rssUrl);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($rssUrl) {
            return $this->fetchArticles($rssUrl);
        });
    }

    /**
     * Fetch articles from the RSS feed.
     *
     * @return array<int, array{title: string, url: string, published_at: string}>
     */
    private function fetchArticles(string $rssUrl): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($rssUrl);

            if (! $response->successful()) {
                Log::warning('Blog RSS feed returned non-successful status', [
                    'url'    => $rssUrl,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseRss($response->body());
        } catch (\Exception $e) {
            Log::warning('Failed to fetch blog RSS feed', [
                'url'   => $rssUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Parse RSS XML and extract articles.
     *
     * @return array<int, array{title: string, url: string, published_at: string}>
     */
    private function parseRss(string $xmlContent): array
    {
        try {
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false || ! isset($xml->channel->item)) {
                return [];
            }

            $articles = [];
            $count = 0;

            foreach ($xml->channel->item as $item) {
                if ($count >= self::ARTICLE_COUNT) {
                    break;
                }

                $title = (string) ($item->title ?? '');
                $url = (string) ($item->link ?? '');
                $pubDate = (string) ($item->pubDate ?? '');

                if (empty($title) || empty($url)) {
                    continue;
                }

                $articles[] = [
                    'title'        => $title,
                    'url'          => $url,
                    'published_at' => $this->formatDate($pubDate),
                ];

                $count++;
            }

            return $articles;
        } catch (\Exception $e) {
            Log::warning('Failed to parse blog RSS XML', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Format the publication date to ISO 8601 format.
     */
    private function formatDate(string $dateString): string
    {
        try {
            $date = new \DateTime($dateString);

            return $date->format('c');
        } catch (\Exception $e) {
            return now()->format('c');
        }
    }
}
