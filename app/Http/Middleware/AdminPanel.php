<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Melindungi seluruh /admin/* pada guard "admin" (PRD v1.3 FR-20/FR-22).
 *
 * Pengunjung diarahkan ke login Admin; akun yang bukan Admin ditolak 403
 * tanpa membocorkan data agregasi. Menyembunyikan menu bukan pengganti ini.
 */
class AdminPanel
{
    public function handle(Request $r, Closure $next)
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            abort_if(Auth::guard('web')->check(), 403, 'Akses panel Admin ditolak.');

            return redirect()->route('admin.login');
        }
        if ($admin->role !== 'admin' || ! $admin->active) {
            Auth::guard('admin')->logout();

            abort(403, 'Akses panel Admin ditolak.');
        }

        Auth::shouldUse('admin');

        return $next($r);
    }
}
