<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendBroadcastRequest;
use App\Models\Broadcast;
use App\Models\NewsletterEmailEvent;
use App\Models\Post;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastController extends Controller
{
    public function __construct(private BroadcastService $broadcastService) {}

    public function index(): Response
    {
        $broadcasts = Broadcast::with('post:id,title,slug')
            ->withCount(['emailEvents as opened_count' => fn ($q) => $q->where('event_type', 'opened')])
            ->latest()
            ->paginate(20)
            ->through(fn (Broadcast $b) => [
                'id' => $b->id,
                'subject' => $b->subject,
                'post_title' => $b->post?->title,
                'status' => $b->status,
                'recipients_count' => $b->recipients_count,
                'sent_count' => $b->sent_count,
                'opened_count' => $b->opened_count,
                'open_rate' => $b->recipients_count > 0
                    ? round($b->opened_count / $b->recipients_count * 100, 1)
                    : null,
                'scheduled_at' => $b->scheduled_at?->format('Y-m-d H:i'),
                'sent_at' => $b->sent_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Broadcasts/Index', [
            'broadcasts' => $broadcasts,
            'recentPosts' => $this->postPayload(Post::published()->orderByDesc('published_at')->take(5)->get()),
            'subscriberCount' => User::newsletterSubscribed()->count(),
        ]);
    }

    /**
     * Search published posts (used when the target isn't in the recent 5).
     */
    public function searchPosts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $posts = Post::published()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")))
            ->orderByDesc('published_at')
            ->take(10)
            ->get();

        return response()->json(['posts' => $this->postPayload($posts)]);
    }

    private function postPayload($posts): array
    {
        return $posts->map(fn (Post $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'published_at' => $p->published_at?->format('Y-m-d'),
        ])->all();
    }

    public function store(SendBroadcastRequest $request): RedirectResponse
    {
        $post = Post::findOrFail($request->integer('post_id'));

        if ($post->status !== 'published') {
            return back()->withErrors(['post_id' => '只有已發佈的文章可以寄送']);
        }

        $scheduledAt = $request->input('scheduled_at');

        if ($scheduledAt) {
            $broadcast = $this->broadcastService->schedule($post, Carbon::parse($scheduledAt));

            return redirect()
                ->route('admin.broadcasts.index')
                ->with('success', '電子報已排程於 ' . $broadcast->scheduled_at->format('Y-m-d H:i') . ' 寄送');
        }

        $broadcast = $this->broadcastService->createImmediate($post);

        return redirect()
            ->route('admin.broadcasts.show', $broadcast)
            ->with('success', "電子報已排入寄送（收件 {$broadcast->recipients_count} 人）");
    }

    public function show(Broadcast $broadcast): Response
    {
        $broadcast->load('post:id,title,slug');

        $openedUserIds = NewsletterEmailEvent::where('broadcast_id', $broadcast->id)
            ->where('event_type', 'opened')
            ->pluck('created_at', 'user_id');

        $recipients = User::whereIn('id', $openedUserIds->keys())
            ->orderBy('email')
            ->paginate(30)
            ->through(fn (User $u) => [
                'email' => $u->email,
                'opened_at' => optional($openedUserIds->get($u->id))->format('Y-m-d H:i'),
            ]);

        $openedCount = $openedUserIds->count();

        return Inertia::render('Admin/Broadcasts/Show', [
            'broadcast' => [
                'id' => $broadcast->id,
                'subject' => $broadcast->subject,
                'post_title' => $broadcast->post?->title,
                'post_url' => $broadcast->post ? "/blog/{$broadcast->post->slug}" : null,
                'status' => $broadcast->status,
                'recipients_count' => $broadcast->recipients_count,
                'sent_count' => $broadcast->sent_count,
                'opened_count' => $openedCount,
                'open_rate' => $broadcast->recipients_count > 0
                    ? round($openedCount / $broadcast->recipients_count * 100, 1)
                    : null,
                'sent_at' => $broadcast->sent_at?->format('Y-m-d H:i'),
            ],
            'openedRecipients' => $recipients,
        ]);
    }
}
