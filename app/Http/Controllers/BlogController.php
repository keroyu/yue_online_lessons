<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use App\Services\OgImageService;
use App\Services\PostService;
use App\Services\SidebarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    public function __construct(private PostService $postService) {}

    /**
     * Public blog index — published posts, newest first.
     */
    public function index(): Response
    {
        $posts = Post::published()
            ->with('tags:id,name,slug')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (Post $post) => $this->cardData($post));

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Single post page. Draft/scheduled 404 for the public; admins may preview.
     */
    public function show(Request $request, Post $post): Response
    {
        $isAdmin = $request->user()?->isAdmin() ?? false;

        if (! $this->isPublic($post) && ! $isAdmin) {
            throw new NotFoundHttpException();
        }

        $this->countView($request, $post, $isAdmin);

        $post->load('tags:id,name,slug', 'relatedCourse:id,name,slug,tagline,thumbnail');

        // Curated related posts first (published, in the admin's order), then fill up to 4
        // with same-tag posts.
        $curatedIds = $post->related_post_ids ?? [];
        $curated = collect();
        if (! empty($curatedIds)) {
            $curated = Post::published()
                ->whereIn('id', $curatedIds)
                ->with('tags:id,name,slug')
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $curatedIds))
                ->take(4)
                ->values();
        }

        $excludeIds = $curated->pluck('id')->push($post->id);
        $fill = Post::published()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->pluck('id')))
            ->with('tags:id,name,slug')
            ->latest('published_at')
            ->take(4 - $curated->count())
            ->get();

        $related = $curated->concat($fill)->map(fn (Post $p) => $this->cardData($p));

        $url = url("/blog/{$post->slug}");

        view()->share('og', $this->postService->ogPayload($post, $url));

        return Inertia::render('Blog/Show', [
            'post' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'body_html' => $this->postService->toHtml($post->body_md),
                'cover_url' => $post->cover_url,
                'published_at' => $post->published_at?->toDateString(),
                'published_at_human' => $post->published_at?->translatedFormat('Y 年 n 月 j 日'),
                'view_count' => $post->view_count,
                'tags' => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
                'url' => $url,
                'related_course' => $post->relatedCourse ? [
                    'name' => $post->relatedCourse->name,
                    'tagline' => $post->relatedCourse->tagline,
                    'thumbnail' => $post->relatedCourse->thumbnail_url,
                    // /go counts the CTA click, then 302s to the course page
                    // with blog UTM appended (002 US10, D17).
                    'url' => url("/go/post/{$post->id}/course/{$post->relatedCourse->id}"),
                ] : null,
            ],
            'related' => $related,
            ...app(SidebarService::class)->widgets(),
        ]);
    }

    /**
     * Auto-generated OG card (navy background + title) for posts without an
     * uploaded OG/cover image. Cached on the public disk; keyed by title hash.
     */
    public function ogImage(Post $post, OgImageService $ogImages): \Illuminate\Http\Response
    {
        $path = $ogImages->resolvePath($post);

        return response(\Illuminate\Support\Facades\Storage::disk('public')->get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Tag archive page. Unknown/empty tag shows an empty state, not an error.
     */
    public function tag(string $slug): Response
    {
        $tag = Tag::where('slug', $slug)->first();

        $posts = $tag
            ? $tag->posts()->published()->orderByDesc('published_at')->paginate(12)
                ->through(fn (Post $post) => $this->cardData($post))
            : Post::whereRaw('1 = 0')->paginate(12);

        return Inertia::render('Blog/Tag', [
            'tag' => $tag ? ['name' => $tag->name, 'slug' => $tag->slug] : ['name' => $slug, 'slug' => $slug],
            'posts' => $posts,
        ]);
    }

    private function isPublic(Post $post): bool
    {
        return $post->status === 'published'
            && ($post->published_at === null || $post->published_at->lte(now()));
    }

    /**
     * Increment view_count once per session; skip admins, non-public posts, and bots. (FR-011)
     */
    private function countView(Request $request, Post $post, bool $isAdmin): void
    {
        if ($isAdmin || ! $this->isPublic($post)) {
            return;
        }

        $ua = (string) $request->userAgent();
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|bing|facebookexternalhit/i', $ua)) {
            return;
        }

        $key = "viewed_post_{$post->id}";
        if ($request->session()->has($key)) {
            return;
        }

        try {
            Post::whereKey($post->id)->increment('view_count');
            $post->view_count++;
            $request->session()->put($key, true);
        } catch (\Throwable $e) {
            Log::warning('Post view count failed', ['post' => $post->id, 'error' => $e->getMessage()]);
        }
    }

    private function cardData(Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'cover_url' => $post->cover_url,
            'url' => "/blog/{$post->slug}",
            'published_at' => $post->published_at?->toDateString(),
            'tags' => $post->relationLoaded('tags')
                ? $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug])
                : [],
        ];
    }
}
