<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubstackRssService
{
    private const RSS_URL = 'https://getwhealthy.substack.com/feed';
    private const CACHE_KEY = 'substack_articles';
    private const CACHE_TTL = 3600; // 1 hour
    private const ARTICLE_COUNT = 5;
    private const TIMEOUT_SECONDS = 5;

    /**
     * Get the latest Substack articles from RSS feed.
     *
     * @return array<int, array{title: string, url: string, published_at: string}>
     */
    public function getArticles(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->fetchArticles();
        });
    }

    /**
     * Fetch articles from the RSS feed.
     *
     * @return array<int, array{title: string, url: string, published_at: string}>
     */
    private function fetchArticles(): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get(self::RSS_URL);

            if (! $response->successful()) {
                Log::warning('Substack RSS feed returned non-successful status', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $this->parseRss($response->body());
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Substack RSS feed', [
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
                    'title' => $title,
                    'url' => $url,
                    'published_at' => $this->formatDate($pubDate),
                ];

                $count++;
            }

            return $articles;
        } catch (\Exception $e) {
            Log::warning('Failed to parse Substack RSS XML', [
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
