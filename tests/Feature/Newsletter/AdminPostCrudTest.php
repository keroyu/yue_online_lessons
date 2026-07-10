<?php

namespace Tests\Feature\Newsletter;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostCrudTest extends TestCase
{
    use RefreshDatabase;

    private ?User $adminUser = null;

    private function admin(): User
    {
        return $this->adminUser ??= User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_admin_can_create_post_with_tags(): void
    {
        $res = $this->actingAs($this->admin())->post('/admin/posts', [
            'title' => 'Prompt 入門',
            'slug' => 'prompt-intro',
            'body_md' => '# Hello',
            'excerpt' => '簡短介紹',
            'status' => 'published',
            'tags' => ['Prompt', 'AI'],
        ]);

        $post = Post::where('slug', 'prompt-intro')->first();
        $this->assertNotNull($post);
        $res->assertRedirect(route('admin.posts.edit', $post));
        $this->assertNotNull($post->published_at, 'published post should stamp published_at');
        $this->assertCount(2, $post->tags);
        $this->assertSame(2, Tag::count());
    }

    public function test_slug_must_be_unique_and_lowercase(): void
    {
        Post::create(['slug' => 'taken', 'title' => 'x', 'body_md' => 'x', 'status' => 'draft']);

        $this->actingAs($this->admin())
            ->post('/admin/posts', ['title' => 'y', 'slug' => 'taken', 'body_md' => 'x', 'status' => 'draft'])
            ->assertSessionHasErrors('slug');

        $this->actingAs($this->admin())
            ->post('/admin/posts', ['title' => 'y', 'slug' => 'Has Space', 'body_md' => 'x', 'status' => 'draft'])
            ->assertSessionHasErrors('slug');
    }

    public function test_scheduled_status_requires_future_published_at(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/posts', ['title' => 'y', 'slug' => 'sched', 'body_md' => 'x', 'status' => 'scheduled'])
            ->assertSessionHasErrors('published_at');
    }

    public function test_destroy_soft_deletes(): void
    {
        $post = Post::create(['slug' => 'gone', 'title' => 'x', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);

        $this->actingAs($this->admin())->delete(route('admin.posts.destroy', $post))->assertRedirect();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_publish_scheduled_command_promotes_due_posts(): void
    {
        $due = Post::create(['slug' => 'due', 'title' => 'x', 'body_md' => 'x', 'status' => 'scheduled', 'published_at' => now()->subMinute()]);
        $future = Post::create(['slug' => 'future', 'title' => 'x', 'body_md' => 'x', 'status' => 'scheduled', 'published_at' => now()->addDay()]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
    }

    public function test_non_admin_cannot_access_post_admin(): void
    {
        $member = User::create(['email' => 'm@example.com', 'role' => 'member']);

        // AdminMiddleware redirects non-admins to home rather than 403.
        $this->actingAs($member)->get('/admin/posts')->assertRedirect('/');
    }
}
