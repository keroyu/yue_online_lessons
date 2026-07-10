<?php

namespace Tests\Feature\Newsletter;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatedPostsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function makePost(string $slug, ?Tag $tag = null): Post
    {
        $p = Post::create(['slug' => $slug, 'title' => $slug, 'body_md' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        if ($tag) {
            $p->tags()->attach($tag->id);
        }
        return $p;
    }

    public function test_admin_can_save_related_post_ids(): void
    {
        $target = $this->makePost('target');
        $rel = $this->makePost('rel');

        $this->actingAs($this->admin())
            ->put("/admin/posts/{$target->id}", [
                'title' => 'target', 'slug' => 'target', 'body_md' => 'x', 'status' => 'published',
                'related_post_ids' => [$rel->id, $target->id], // includes self → should be stripped
            ])
            ->assertRedirect();

        $this->assertSame([$rel->id], $target->fresh()->related_post_ids);
    }

    public function test_curated_related_shown_first_then_tag_fill(): void
    {
        $tag = Tag::create(['name' => 'T', 'slug' => 't']);
        $main = $this->makePost('main', $tag);
        $tagMate = $this->makePost('tagmate', $tag);       // same tag, would auto-fill
        $curated = $this->makePost('curated');             // no shared tag, but manually curated

        $main->update(['related_post_ids' => [$curated->id]]);

        $this->get('/blog/main')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('related.0.slug', 'curated')   // curated first
                ->where('related.1.slug', 'tagmate')); // then same-tag fill
    }

    public function test_search_endpoint_excludes_self(): void
    {
        $a = $this->makePost('alpha');
        $this->makePost('alpine');

        $res = $this->actingAs($this->admin())
            ->getJson('/admin/posts/search?q=alp&exclude=' . $a->id)
            ->assertOk();

        $slugs = collect($res->json('posts'))->pluck('title');
        $this->assertFalse($slugs->contains('alpha'));
        $this->assertTrue($slugs->contains('alpine'));
    }
}
