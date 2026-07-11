<?php

namespace Tests\Feature\Newsletter;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the 文章管理 list search + tag quick-filter (PostController@index).
 */
class AdminPostSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function makePost(string $slug, string $title, ?Tag $tag = null): Post
    {
        $p = Post::create(['slug' => $slug, 'title' => $title, 'body_md' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        if ($tag) {
            $p->tags()->attach($tag->id);
        }
        return $p;
    }

    public function test_keyword_search_matches_tag_name(): void
    {
        $tag = Tag::create(['name' => '投資理財', 'slug' => 'invest']);
        $this->makePost('alpha', 'Alpha', $tag);   // tagged
        $this->makePost('beta', 'Beta');           // untagged, unrelated title
        $this->makePost('gamma', 'Gamma', $tag);   // tagged

        // No post title contains 投資理財, so only the two tagged posts should match.
        $this->actingAs($this->admin())
            ->get('/admin/posts?search=' . urlencode('投資理財'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Admin/Posts/Index')
                ->has('posts.data', 2));
    }

    public function test_keyword_search_still_matches_title_and_slug(): void
    {
        $admin = $this->admin();
        $this->makePost('alpha', 'Alpha');
        $this->makePost('beta', 'Beta');

        $this->actingAs($admin)
            ->get('/admin/posts?search=Alpha')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('posts.data', 1)->where('posts.data.0.slug', 'alpha'));

        $this->actingAs($admin)
            ->get('/admin/posts?search=beta')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('posts.data', 1)->where('posts.data.0.slug', 'beta'));
    }

    public function test_tag_chip_filters_by_slug(): void
    {
        $invest = Tag::create(['name' => '投資理財', 'slug' => 'invest']);
        $mindset = Tag::create(['name' => '思維升級', 'slug' => 'mindset']);
        $this->makePost('a', 'A', $invest);
        $this->makePost('b', 'B', $invest);
        $this->makePost('c', 'C', $mindset);

        $this->actingAs($this->admin())
            ->get('/admin/posts?tag=invest')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('posts.data', 2));
    }

    public function test_index_exposes_top_5_popular_tags_with_slug(): void
    {
        $admin = $this->admin();

        // 6 tags with descending usage; only the top 5 should surface.
        foreach (['t1' => 5, 't2' => 4, 't3' => 3, 't4' => 2, 't5' => 1, 't6' => 1] as $slug => $count) {
            $tag = Tag::create(['name' => strtoupper($slug), 'slug' => $slug]);
            for ($i = 0; $i < $count; $i++) {
                $this->makePost("$slug-$i", "$slug-$i", $tag);
            }
        }

        $this->actingAs($admin)
            ->get('/admin/posts')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('popularTags', 5)
                ->where('popularTags.0.slug', 't1')
                ->where('popularTags.0.name', 'T1')
                ->where('filters.tag', ''));
    }
}
