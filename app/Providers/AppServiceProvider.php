<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Document;
use App\Models\Event;
use App\Policies\DocumentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::define('manage-event', fn ($u, Event $e) => $u->active && $u->role === 'organizer' && $e->organizer_id === $u->id);
        Gate::define('view-application', fn ($u, Application $a) => $u->active && ($a->volunteer_id === $u->id || ($u->role === 'organizer' && $a->submitted_at && $a->event->organizer_id === $u->id)));
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(5)->by(strtolower($r->input('email', '')).'|'.$r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?? $r->ip()));
    }
}
