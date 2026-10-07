<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityHeaders
{
    public function handle(Request $r, Closure $next)
    {
        Auth::shouldUse($r->is('admin/*') ? 'admin' : 'web');
        if (! $r->is('admin/*') && $r->user() && ! $r->user()->active) {
            auth('web')->logout();
            $r->session()->regenerate(true);
            $r->session()->regenerateToken();
            abort(403, 'Akun dinonaktifkan.');
        }$response = $next($r);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        // Browsers can apply form-action to the redirect following checkout POST.
        $formAction = $r->routeIs('organizer.event', 'organizer.pay')
            ? "'self' https://app.sandbox.midtrans.com"
            : "'self'";
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action ".$formAction);
        if ($r->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }if ($r->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
