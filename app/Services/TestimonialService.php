<?php

namespace App\Services;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Client testimonials collected after a consultation (011 US40).
 *
 * The link is the credential (D159): whoever holds the token may submit once,
 * the points are paid on submission (D160), and publishing is a separate,
 * manual review step that never touches the points.
 */
class TestimonialService
{
    public const REWARD_SETTING_KEY = 'testimonial_reward_points';

    public const DEFAULT_REWARD = 100;

    public function __construct(private PointService $pointService) {}

    public function rewardPoints(): int
    {
        return max(0, (int) SiteSetting::get(self::REWARD_SETTING_KEY, self::DEFAULT_REWARD));
    }

    /**
     * The shareable link for one lead (FR-239). The token is issued on the
     * first copy and reused afterwards, so every follow-up email carries the
     * same address (D162).
     *
     * @throws ValidationException when the consultation has not happened
     */
    public function issueLink(HighTicketLead $lead): string
    {
        $lead->loadMissing('slots');

        if (!$lead->isTestimonialEligible()) {
            throw ValidationException::withMessages([
                'lead' => '這筆預約尚未完成面談，無法產生評語連結',
            ]);
        }

        if ($lead->testimonial_token === null) {
            $lead->forceFill(['testimonial_token' => Str::random(40)])->save();
        }

        return route('testimonials.create', $lead->testimonial_token);
    }

    /**
     * Course student or consultation client (011 FR-246): whether this email's
     * member holds a live purchase of the lead's course. Shared by the form's
     * preview and the stored value, so the two can never disagree.
     */
    public function identityFor(HighTicketLead $lead, ?User $user = null): string
    {
        $user ??= User::where('email', $lead->email)->first();
        $course = $lead->course ?? Course::find($lead->course_id);

        return $course?->hasPaidAccessForUser($user) ? 'student' : 'consultation';
    }

    /**
     * Published testimonials of one course in public shape — no consultation
     * time (FR-248). The sales page and the testimonial form both read this,
     * so the two lists can never drift (FR-245, FR-251).
     *
     * @return list<array{id: int, nickname: string, title: ?string, content: string, identity_label: string}>
     */
    public function publishedFor(int $courseId, ?int $exceptId = null): array
    {
        return Testimonial::published()
            ->where('course_id', $courseId)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderByDesc('consulted_at')
            ->limit(30)
            ->get(['id', 'nickname', 'title', 'content', 'identity'])
            ->map(fn (Testimonial $t) => $t->only(['id', 'nickname', 'title', 'content', 'identity_label']))
            ->all();
    }

    /** When the consultation began — the first unit the lead held, in UTC. */
    public function consultedAt(HighTicketLead $lead): ?\Illuminate\Support\Carbon
    {
        return $lead->slots()->first()?->starts_at;
    }

    /**
     * Store the testimonial, make sure the client has an account, and pay the
     * reward — all or nothing (FR-241). The lead row lock serialises a double
     * submit; the unique `lead_id` catches anything that slips past it.
     *
     * @param  array{nickname: string, title: ?string, content: string}  $data
     *
     * @throws ValidationException when this lead already left a testimonial
     */
    public function submit(HighTicketLead $lead, array $data): Testimonial
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead = HighTicketLead::whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if (Testimonial::where('lead_id', $lead->id)->exists()) {
                throw ValidationException::withMessages(['content' => '你已經留過評語，謝謝！']);
            }

            $consultedAt = $this->consultedAt($lead);

            if ($consultedAt === null) {
                throw ValidationException::withMessages(['content' => '這個評語連結已失效']);
            }

            // Same creation attributes as a lead conversion: an existing
            // member keeps whatever they already have (D161).
            $user = User::firstOrCreate(
                ['email' => $lead->email],
                [
                    'nickname' => $lead->name,
                    'phone'    => $lead->phone,
                    'password' => Str::password(16),
                ]
            );

            $points = $this->rewardPoints();

            $testimonial = Testimonial::create([
                'lead_id'       => $lead->id,
                'user_id'       => $user->id,
                'course_id'     => $lead->course_id,
                'nickname'      => $data['nickname'],
                'title'         => $data['title'] ?? null,
                'content'       => $data['content'],
                'consulted_at'  => $consultedAt,
                // Stored, not recomputed on display: a refund must not quietly
                // relabel a published testimonial (D165).
                'identity'      => $this->identityFor($lead, $user),
                'status'        => 'pending',
                'reward_points' => $points,
            ]);

            if ($points > 0) {
                $this->pointService->award($user, $points, 'earn_testimonial', 'testimonial', $testimonial->id, '諮詢評語回饋');
            }

            return $testimonial;
        });
    }

    /**
     * Review changes from the modal: visibility and/or identity (FR-244,
     * FR-249). Points are never touched.
     *
     * @param  array{status?: string, identity?: string}  $data
     */
    public function update(Testimonial $testimonial, array $data): void
    {
        $testimonial->fill(array_intersect_key($data, array_flip(['status', 'identity'])));

        if ($testimonial->status === 'published' && $testimonial->published_at === null) {
            $testimonial->published_at = now();
        }

        $testimonial->save();
    }

    /** Hard delete. The reward stays — it paid for writing, not for being shown. */
    public function delete(Testimonial $testimonial): void
    {
        $testimonial->delete();
    }
}
