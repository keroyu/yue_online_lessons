<?php

namespace Tests\Feature\Member;

use App\Models\Assignment;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserSocialLinkTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $email = 'member@example.com'): User
    {
        return User::create(['email' => $email, 'role' => 'member']);
    }

    public function test_member_can_add_social_link(): void
    {
        $user = $this->member();

        $this->actingAs($user)
            ->post('/member/social-links', ['platform' => 'instagram', 'url' => 'https://instagram.com/foo'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_social_links', [
            'user_id' => $user->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/foo',
            'sort_order' => 1,
        ]);
    }

    public function test_same_platform_can_be_added_twice(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/member/social-links', ['platform' => 'instagram', 'url' => 'https://instagram.com/a']);
        $this->actingAs($user)->post('/member/social-links', ['platform' => 'instagram', 'url' => 'https://instagram.com/b'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $user->socialLinks()->count());
    }

    public function test_non_https_url_is_rejected(): void
    {
        $this->actingAs($this->member())
            ->post('/member/social-links', ['platform' => 'blog', 'url' => 'http://example.com'])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('user_social_links', 0);
    }

    public function test_platform_outside_whitelist_is_rejected(): void
    {
        $this->actingAs($this->member())
            ->post('/member/social-links', ['platform' => 'podcast', 'url' => 'https://example.com'])
            ->assertSessionHasErrors('platform');
    }

    public function test_sixth_link_is_rejected(): void
    {
        $user = $this->member();
        foreach (range(1, 5) as $i) {
            $user->socialLinks()->create(['platform' => 'blog', 'url' => "https://example.com/{$i}", 'sort_order' => $i]);
        }

        $this->actingAs($user)
            ->post('/member/social-links', ['platform' => 'blog', 'url' => 'https://example.com/6'])
            ->assertSessionHasErrors('url');

        $this->assertSame(5, $user->socialLinks()->count());
    }

    public function test_member_can_delete_own_link_but_not_others(): void
    {
        $user = $this->member();
        $other = $this->member('other@example.com');
        $own = $user->socialLinks()->create(['platform' => 'blog', 'url' => 'https://example.com/me', 'sort_order' => 1]);
        $theirs = $other->socialLinks()->create(['platform' => 'blog', 'url' => 'https://example.com/them', 'sort_order' => 1]);

        $this->actingAs($user)->delete("/member/social-links/{$theirs->id}")->assertNotFound();
        $this->assertDatabaseHas('user_social_links', ['id' => $theirs->id]);

        $this->actingAs($user)->delete("/member/social-links/{$own->id}")->assertRedirect();
        $this->assertDatabaseMissing('user_social_links', ['id' => $own->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post('/member/social-links', ['platform' => 'blog', 'url' => 'https://example.com'])
            ->assertRedirect('/login');
    }

    public function test_settings_page_includes_social_links(): void
    {
        $user = $this->member();
        $user->socialLinks()->create(['platform' => 'youtube', 'url' => 'https://youtube.com/@foo', 'sort_order' => 1]);

        $this->actingAs($user)->get('/member/settings')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Settings')
                ->has('socialLinks', 1)
                ->where('socialLinks.0.platform', 'youtube')
            );
    }

    public function test_admin_homework_index_includes_student_social_links(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);
        $student = $this->member('student@example.com');
        $student->socialLinks()->create(['platform' => 'threads', 'url' => 'https://threads.net/@foo', 'sort_order' => 1]);

        $course = Course::create([
            'name' => 'C', 'slug' => 'c-1', 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'sort_order' => 1]);
        $lesson = Lesson::create(['course_id' => $course->id, 'chapter_id' => $chapter->id, 'title' => 'L']);
        $assignment = Assignment::create(['lesson_id' => $lesson->id, 'md_content' => 'hw', 'is_published' => true]);
        Comment::create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'content' => 'my answer']);

        $this->actingAs($admin)->get('/admin/homework')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Homework/Index')
                ->where('submissions.data.0.user.social_links.0.platform', 'threads')
                ->where('submissions.data.0.user.social_links.0.url', 'https://threads.net/@foo')
            );
    }
}
