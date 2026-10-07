<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Event;
use App\Models\Notice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $r)
    {
        $user = $r->user();
        $notices = Notice::where('user_id', $user->id)->latest()->take(8)->get();
        if ($user->role === 'volunteer') {
            $own = Application::where('volunteer_id', $user->id);

            return view('activity', compact('user', 'notices') + [
                'applications' => (clone $own)->with('event', 'position')->latest()->take(6)->get(),
                'assessments' => (clone $own)->where('status', 'assessment_pending')->with('event', 'position', 'attempt')->get(),
                'repairs' => (clone $own)->whereNotIn('status', ['withdrawn', 'rejected'])->whereHas('documents', fn ($q) => $q->whereIn('status', ['scan_failed', 'rejected']))->with('event')->get(),
                'upcoming' => (clone $own)->where('status', 'accepted')->whereHas('event', fn ($q) => $q->whereNotIn('lifecycle', ['cancelled', 'completed'])->where('ends_at', '>=', now()))->with('event', 'position')->get()->sortBy('event.starts_at'),
                'profileChecks' => ['Kota' => (bool) $user->city, 'Skill' => $user->skills()->exists(), 'Ketersediaan waktu' => ! empty($user->availability), 'Verifikasi email' => $user->hasVerifiedEmail()],
            ]);
        }
        $events = Event::where('organizer_id', $user->id)->with('orders', 'package')->withCount(['applications as review_count' => fn ($q) => $q->where('status', 'under_review'), 'applications as attendance_count' => fn ($q) => $q->where('status', 'accepted')->where('attendance', 'not_recorded')])->orderBy('starts_at')->paginate(12);

        return view('activity', compact('user', 'notices', 'events'));
    }

    public function readNotice(Request $r, Notice $notice)
    {
        abort_unless($r->user()->active && $notice->user_id === $r->user()->id, 403);
        $notice->update(['read_at' => now()]);

        return back();
    }
}
