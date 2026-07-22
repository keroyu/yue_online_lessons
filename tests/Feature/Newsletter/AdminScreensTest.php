<?php

namespace Tests\Feature\Newsletter;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_admin_post_screens_render(): void
    {
        $admin = $this->admin();
        $post = Post::create(['slug' => 'x', 'title' => 'X', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);

        $this->actingAs($admin)->get('/admin/posts')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Posts/Index')->has('posts.data', 1));

        $this->actingAs($admin)->get('/admin/posts/create')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Posts/Create')->has('allTags'));

        $this->actingAs($admin)->get("/admin/posts/{$post->id}/edit")
            ->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Posts/Edit')->where('post.slug', 'x'));
    }

    public function test_all_tags_ordered_by_usage(): void
    {
        $admin = $this->admin();
        $hot = \App\Models\Tag::create(['name' => '熱門', 'slug' => 'hot']);
        $cold = \App\Models\Tag::create(['name' => '冷門', 'slug' => 'cold']);

        // 熱門 used by 2 posts, 冷門 by 1
        foreach (['a', 'b'] as $s) {
            Post::create(['slug' => "p-$s", 'title' => $s, 'body_md' => 'x', 'status' => 'published', 'published_at' => now()])
                ->tags()->attach($hot->id);
        }
        Post::create(['slug' => 'p-c', 'title' => 'c', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()])
            ->tags()->attach($cold->id);

        $this->actingAs($admin)->get('/admin/posts/create')
            ->assertInertia(fn ($p) => $p->where('allTags.0', '熱門')->where('allTags.1', '冷門'));
    }

    public function test_admin_broadcast_screens_render(): void
    {
        $admin = $this->admin();
        Post::create(['slug' => 'p', 'title' => 'P', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);

        $this->actingAs($admin)->get('/admin/broadcasts')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Broadcasts/Index')->has('recentPosts', 1));
    }
}
