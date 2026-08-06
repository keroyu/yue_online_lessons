<?php

namespace App\Providers;

use App\Listeners\BlockSuppressedRecipients;
use App\Listeners\RecordEmailSuppression;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Policies\CoursePolicy;
use App\Policies\PurchasePolicy;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Resend\Laravel\Events\EmailBounced;
use Resend\Laravel\Events\EmailComplained;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);

        Event::listen(EmailBounced::class, [RecordEmailSuppression::class, 'handleBounced']);
        Event::listen(EmailComplained::class, [RecordEmailSuppression::class, 'handleComplained']);
        Event::listen(MessageSending::class, BlockSuppressedRecipients::class);

        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            $this->configureResendWebhookSecret($event->route);
        });
    }

    /**
     * Feed the DB-managed webhook secret into the package's config, scoped to
     * the webhook route only — SiteSetting::get() has no cache, so doing this
     * unconditionally on every boot would add a DB query to every request (D27).
     *
     * This has to run on RouteMatched rather than in boot() directly: the
     * package's WebhookController reads config('resend.webhook.secret') in its
     * constructor, and the controller is instantiated while the router gathers
     * middleware for the matched route — which happens right after RouteMatched
     * fires, but boot() itself runs once per process before any route (or, in
     * tests, once per test case) and can't see which route is being hit.
     */
    private function configureResendWebhookSecret($route): void
    {
        if ($route->getName() !== 'resend.webhook') {
            return;
        }

        $secret = SiteSetting::get('resend_webhook_secret');
        if ($secret) {
            config(['resend.webhook.secret' => $secret]);
        }
    }
}
