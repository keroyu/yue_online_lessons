<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class BlogFeedController extends Controller
{
    /**
     * Native RSS 2.0 feed of the latest published posts.
     */
    public function index(): Response
    {
        $posts = Post::published()
            ->orderByDesc('published_at')
            ->take(20)
            ->get();

        $siteName = config('app.name', 'Your Time Bank');
        $self = url('/blog/feed');
        $blogUrl = url('/blog');

        $items = $posts->map(function (Post $post) {
            $link = url("/blog/{$post->slug}");
            $title = $this->esc($post->title);
            $desc = $this->esc((string) $post->excerpt);
            $date = optional($post->published_at)->toRssString();

            return <<<XML
    <item>
      <title>{$title}</title>
      <link>{$link}</link>
      <guid isPermaLink="true">{$link}</guid>
      <description>{$desc}</description>
      <pubDate>{$date}</pubDate>
    </item>
XML;
        })->implode("\n");

        $siteNameEsc = $this->esc($siteName);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{$siteNameEsc}</title>
    <link>{$blogUrl}</link>
    <description>{$siteNameEsc} — 最新文章</description>
    <language>zh-TW</language>
    <atom:link href="{$self}" rel="self" type="application/rss+xml" />
{$items}
  </channel>
</rss>
XML;

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
