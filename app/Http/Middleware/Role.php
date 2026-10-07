<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Role untuk web utama (Volunteer/Organizer). Admin memakai guard terpisah
 * melalui AdminPanel, sehingga akun Admin tidak memperoleh area pengguna.
 */
class Role
{
    public function handle(Request $r, Closure $next, string $role)
    {
        abort_unless($r->user()?->active && $r->user()->role === $role, 403);

        return $next($r);
    }
}
