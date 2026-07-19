<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Course;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Paginated admin list with status filter + keyword search.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $tag = trim((string) $request->input('tag', ''));
        $sort = $request->input('sort') === 'views' ? 'view_count' : 'created_at';

        $posts = Post::query()
            ->when(in_array($status, ['draft', 'scheduled', 'published'], true), fn ($q) => $q->where('status', $status))
            // Keyword matches title / slug / tag name.
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', "%{$search}%"))))
            // Quick tag-chip filter (by slug).
            ->when($tag !== '', fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $tag)))
            ->orderByDesc($sort)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'is_featured' => $post->is_featured,
                'view_count' => $post->view_count,
                'published_at' => $post->published_at?->format('Y-m-d H:i'),
                'broadcasts_count' => $post->broadcasts()->count(),
            ]);

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => ['status' => $status, 'search' => $search, 'tag' => $tag, 'sort' => $request->input('sort')],
            'popularTags' => Tag::withCount('posts')
                ->orderByDesc('posts_count')
                ->orderBy('name')
                ->take(5)
                ->get(['name', 'slug'])
                ->map(fn (Tag $t) => ['name' => $t->name, 'slug' => $t->slug])
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Posts/Create', [
            'courses' => $this->courseOptions(),
            'popularTags' => $this->popularTags(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $this->prepare($request);
        $tags = $request->input('tags');

        $post = Post::create($data);
        $this->syncTags($post, $tags);
        $this->syncRelated($post, $request->input('related_post_ids'));

        return redirect()
            ->route('admin.posts.index')
            ->with('success', '文章已建立');
    }

    public function edit(Post $post): Response
    {
        $post->load('tags:id,name', 'images');

        return Inertia::render('Admin/Posts/Edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'body_md' => $post->body_md,
                'excerpt' => $post->excerpt,
                'seo_title' => $post->seo_title,
                'meta_description' => $post->meta_description,
                'cover_url' => $post->cover_url,
                'og_url' => $post->og_image_path ? Storage::url($post->og_image_path) : null,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
                'is_featured' => $post->is_featured,
                'related_course_id' => $post->related_course_id,
                'view_count' => $post->view_count,
                'tags' => $post->tags->pluck('name'),
                'related' => $this->relatedPayload($post->related_post_ids),
            ],
            'images' => $post->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'filename' => $image->filename,
            ]),
            'courses' => $this->courseOptions(),
            'popularTags' => $this->popularTags(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $this->prepare($request, $post);
        $tags = $request->input('tags');

        $post->update($data);
        $this->syncTags($post, $tags);
        $this->syncRelated($post, $request->input('related_post_ids'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', '文章已更新');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('success', '文章已刪除');
    }

    /**
     * Normalize validated data: handle uploads, author, publish time.
     */
    private function prepare(StorePostRequest|UpdatePostRequest $request, ?Post $post = null): array
    {
        $data = $request->safe()->except(['cover_image', 'og_image', 'tags']);

        if ($request->hasFile('cover_image')) {
            if ($post?->cover_image_path) {
                Storage::disk('public')->delete($post->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('post-images', 'public');
        }

        if ($request->hasFile('og_image')) {
            if ($post?->og_image_path) {
                Storage::disk('public')->delete($post->og_image_path);
            }
            $data['og_image_path'] = $request->file('og_image')->store('post-images', 'public');
        }

        // Publishing now with no explicit time → stamp now.
        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($post === null) {
            $data['author_id'] = auth()->id();
        }

        return $data;
    }

    /**
     * firstOrCreate tags by name and sync the pivot. null = leave untouched.
     */
    private function syncTags(Post $post, ?array $names): void
    {
        if ($names === null) {
            return;
        }

        $ids = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = Str::lower(preg_replace('/\s+/u', '-', $name));
            }
            $tag = Tag::firstOrCreate(['name' => $name], ['slug' => $slug]);
            $ids[] = $tag->id;
        }

        $post->tags()->sync($ids);
    }

    /**
     * Save curated related post IDs (ordered, de-duped, self excluded). null = leave untouched.
     */
    private function syncRelated(Post $post, ?array $ids): void
    {
        if ($ids === null) {
            return;
        }

        $clean = collect($ids)
            ->map(fn ($i) => (int) $i)
            ->unique()
            ->reject(fn ($i) => $i === $post->id)
            ->values()
            ->all();

        $post->update(['related_post_ids' => $clean ?: null]);
    }

    /**
     * Resolve stored related IDs to {id,title}, preserving the curated order.
     */
    private function relatedPayload(?array $ids): array
    {
        $ids = $ids ?? [];
        if (empty($ids)) {
            return [];
        }

        return Post::whereIn('id', $ids)
            ->get(['id', 'title'])
            ->sortBy(fn ($p) => array_search($p->id, $ids))
            ->values()
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])
            ->all();
    }

    /**
     * Search posts to attach as related (excludes the current post).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $exclude = $request->integer('exclude');

        $posts = Post::query()
            ->when($exclude, fn ($x) => $x->where('id', '!=', $exclude))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")))
            ->orderByDesc('published_at')
            ->take(10)
            ->get(['id', 'title'])
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title]);

        return response()->json(['posts' => $posts]);
    }

    /**
     * Top 10 tags by usage, for quick-select in the form.
     */
    private function popularTags(): array
    {
        return Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->take(10)
            ->pluck('name')
            ->all();
    }

    private function courseOptions(): array
    {
        return Course::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }
}
