<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::guard('web')->attempt($v + ['active' => true, 'role' => ['volunteer', 'organizer']], $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai. Akun Admin menggunakan login Admin.'])->onlyInput('email');
        }$r->session()->regenerate();

        $r->session()->forget('url.intended');

        return redirect()->route(Auth::guard('web')->user()->role.'.activity');
    }

    public function register(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:190|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()], 'role' => 'required|in:volunteer,organizer']);
        $u = new User(collect($v)->except('role')->all());
        $u->role = $v['role'];
        $u->save();
        event(new Registered($u));
        Auth::login($u);
        $r->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function logout(Request $r)
    {
        Auth::guard('web')->logout();
        $r->session()->forget('url.intended');
        $r->session()->regenerate(true);
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function adminLogin(Request $r)
    {
        if ($r->isMethod('get')) {
            return Auth::guard('admin')->check() ? redirect()->route('admin.dashboard') : view('admin.login');
        }
        $v = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::guard('admin')->attempt($v + ['active' => true, 'role' => 'admin'])) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        $r->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function adminLogout(Request $r)
    {
        Auth::guard('admin')->logout();
        $r->session()->forget('url.intended');
        $r->session()->regenerate(true);
        $r->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function forgot(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        return back()->with('success', 'Jika alamat terdaftar, tautan pemulihan akan dikirim.');
    }

    public function reset(Request $r)
    {
        $r->validate(['email' => 'required|email', 'token' => 'required', 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()]]);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $u, string $p) {
            $u->forceFill(['password' => $p, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $u->id)->delete();
            event(new PasswordReset($u));
        });

        return $status === Password::PasswordReset ? redirect('/login')->with('success', 'Kata sandi diperbarui.') : back()->withErrors(['email' => 'Tautan tidak valid atau sudah kedaluwarsa.']);
    }
}
