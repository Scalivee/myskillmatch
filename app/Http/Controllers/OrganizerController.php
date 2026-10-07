<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Application;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Position;
use App\Models\Skill;
use App\Services\PaymentService;
use App\Services\RecruitmentService;
use App\Support\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizerController extends Controller
{
    public function transactions(Request $r)
    {
        return view('organizer.transactions', ['orders' => Order::where('user_id', $r->user()->id)->with('event')->latest()->paginate(20)]);
    }

    public function index(Request $r)
    {
        return view('organizer.events', ['events' => $r->user()->events()->latest()->paginate(12)]);
    }

    public function create()
    {
        return view('organizer.form', ['event' => new Event, 'packages' => Package::where('active', true)->get()]);
    }

    private function values(EventRequest $r): array
    {
        $v = $r->validated();
        foreach (['starts_at', 'ends_at', 'deadline', 'registration_opens_at'] as $key) {
            $v[$key] = Carbon::parse($v[$key], 'Asia/Jakarta')->utc();
        }$p = Package::findOrFail($v['package_id']);
        if (! $v['deadline']->isFuture() || ! $v['starts_at']->isFuture()) {
            throw ValidationException::withMessages(['deadline' => 'Tenggat dan awal event harus berada di masa depan (WIB).']);
        }if (! $p->active || $v['deadline']->diffInSeconds($v['registration_opens_at'], true) > $p->days_limit * 86400) {
            throw ValidationException::withMessages(['package_id' => 'Paket tidak aktif atau durasi pendaftaran melebihi batas paket.']);
        }

        return $v;
    }

    public function store(EventRequest $r)
    {
        $e = Event::create($this->values($r) + ['organizer_id' => $r->user()->id]);

        return redirect()->route('organizer.event', $e);
    }

    public function edit(Event $event)
    {
        Gate::authorize('manage-event', $event);
        abort_if($event->applications()->exists() || ! in_array($event->moderation, ['draft', 'revision_required']), 403);

        return view('organizer.form', ['event' => $event, 'packages' => Package::where('active', true)->get()]);
    }

    public function update(EventRequest $r, Event $event)
    {
        Gate::authorize('manage-event', $event);
        DB::transaction(function () use ($event, $r) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_if($e->applications()->exists() || ! in_array($e->moderation, ['draft', 'revision_required']), 403);
            $v = $this->values($r);
            if ($e->positions()->where(fn ($q) => $q->where('starts_at', '<', $v['starts_at'])->orWhere('ends_at', '>', $v['ends_at']))->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal event harus mencakup seluruh posisi.']);
            }if ($e->positions()->count() > Package::find($v['package_id'])->positions_limit) {
                throw ValidationException::withMessages(['package_id' => 'Jumlah posisi melebihi paket.']);
            }$e->update($v);
        });

        return redirect()->route('organizer.event', $event);
    }

    public function show(Event $event)
    {
        Gate::authorize('manage-event', $event);

        return view('organizer.event', ['event' => $event->load(['positions' => fn ($q) => $q->withCount(['applications as accepted_count' => fn ($q) => $q->where('status', 'accepted')]), 'orders', 'package']), 'skills' => Skill::where('active', true)->get()]);
    }

    public function addPosition(Request $r, Event $event)
    {
        Gate::authorize('manage-event', $event);
        $v = $r->validate(['name' => 'required|string|max:100', 'quota' => 'required|integer|min:1|max:1000', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'skill_ids' => 'required|array|min:1|max:30', 'skill_ids.*' => 'integer|exists:skills,id', 'required_skill_ids' => 'nullable|array', 'required_skill_ids.*' => 'integer|exists:skills,id', 'duration_minutes' => 'required|integer|min:1|max:120', 'questions' => 'required|array|min:1|max:10', 'questions.*.text' => 'required|string|max:1000', 'questions.*.options' => 'required|array|size:4', 'questions.*.options.*' => 'required|string|max:300', 'questions.*.answer' => 'required|integer|between:0,3']);
        $r->validate(['skill_ids.*' => [Rule::exists('skills', 'id')->where('active', true)]]);
        $v['required_skill_ids'] = $v['required_skill_ids'] ?? [];
        if (array_diff($v['required_skill_ids'], $v['skill_ids'])) {
            throw ValidationException::withMessages(['required_skill_ids' => 'Skill wajib harus termasuk skill posisi.']);
        }foreach (['starts_at', 'ends_at'] as $key) {
            $v[$key] = Carbon::parse($v[$key], 'Asia/Jakarta')->utc();
        }foreach (['full_availability', 'same_city', 'cv_required'] as $key) {
            $v[$key] = $r->boolean($key);
        }
        DB::transaction(function () use ($event, $v) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_if($e->applications()->exists() || ! in_array($e->moderation, ['draft', 'revision_required']), 403);
            if ($e->positions()->count() >= $e->limits()['positions_limit']) {
                throw ValidationException::withMessages(['name' => 'Batas posisi paket tercapai.']);
            }if ($v['starts_at'] < $e->starts_at || $v['ends_at'] > $e->ends_at) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal posisi harus berada dalam jadwal event.']);
            }$e->positions()->create($v);
        });

        return back()->with('success', 'Posisi dan assessment disimpan.');
    }

    public function removePosition(Event $event, Position $position)
    {
        Gate::authorize('manage-event', $event);
        abort_unless($position->event_id === $event->id, 404);
        DB::transaction(function () use ($event, $position) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_if($e->applications()->exists() || ! in_array($e->moderation, ['draft', 'revision_required']), 403);
            $position->delete();
        });

        return back();
    }

    public function submit(Event $event)
    {
        Gate::authorize('manage-event', $event);
        DB::transaction(function () use ($event) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_unless(in_array($e->moderation, ['draft', 'revision_required']) && $e->positions()->exists(), 422);
            abort_unless($e->deadline->isFuture(), 422);
            $e->update(['moderation' => 'pending_review', 'package_snapshot' => $e->package->snapshot()]);
            Activity::log('event.submitted', (string) $e->id);
        });

        return back()->with('success', 'Event diajukan untuk verifikasi Admin.');
    }

    public function applicants(Request $r, Event $event)
    {
        Gate::authorize('manage-event', $event);
        $q = $event->applications()->with('volunteer', 'position')->whereNotNull('submitted_at');
        if ($r->filled('status')) {
            $q->where('status', $r->string('status')->toString());
        }

        return view('organizer.applicants', ['event' => $event, 'applications' => $q->orderByDesc('match_score')->orderByDesc('assessment_score')->orderBy('submitted_at')->orderBy('id')->paginate(20)->withQueryString()]);
    }

    public function decision(Request $r, Application $application, RecruitmentService $service)
    {
        Gate::authorize('manage-event', $application->event);
        $v = $r->validate(['decision' => 'required|in:accepted,rejected', 'reason' => 'required_if:decision,rejected|nullable|string|max:1000']);
        $service->decide($application, $v['decision'], $v['reason'] ?? '');

        return back()->with('success', 'Keputusan seleksi disimpan.');
    }

    public function attendance(Request $r, Event $event)
    {
        Gate::authorize('manage-event', $event);

        return view('organizer.attendance', ['event' => $event, 'applications' => $event->applications()->with('volunteer', 'position')->where('status', 'accepted')->get()]);
    }

    public function saveAttendance(Request $r, Event $event)
    {
        Gate::authorize('manage-event', $event);
        $v = $r->validate(['attendance' => 'required|array', 'attendance.*' => 'required|in:present,absent,not_recorded']);
        DB::transaction(function () use ($event, $v) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_unless($e->starts_at->isPast() && ! in_array($e->lifecycle, ['completed', 'cancelled']), 422);
            foreach ($v['attendance'] as $id => $status) {
                $a = $e->applications()->where('status', 'accepted')->findOrFail($id);
                $a->update(['attendance' => $status]);
                Activity::log('attendance.updated', (string) $a->id, ['status' => $status]);
            }
        });

        return back()->with('success', 'Kehadiran tersimpan.');
    }

    public function complete(Event $event)
    {
        Gate::authorize('manage-event', $event);
        DB::transaction(function () use ($event) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_unless($e->ends_at->isPast() && ! in_array($e->lifecycle, ['completed', 'cancelled']), 422);
            if ($e->applications()->where('status', 'accepted')->where('attendance', 'not_recorded')->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Lengkapi kehadiran seluruh peserta.']);
            }$e->update(['lifecycle' => 'completed']);
            Activity::log('event.completed', (string) $e->id);
        });

        return back()->with('success', 'Event selesai; riwayat peserta diperbarui.');
    }

    public function pay(Event $event, PaymentService $payments)
    {
        Gate::authorize('manage-event', $event);
        $order = DB::transaction(function () use ($event) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_unless($e->moderation === 'approved' && $e->lifecycle === 'upcoming' && $e->deadline->isFuture(), 422);
            $snap = $e->limits();
            if (empty($snap['price'])) {
                throw ValidationException::withMessages(['payment' => 'Harga paket belum diatur atau paket gratis.']);
            }if ($e->orders()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Event sudah dibayar.']);
            }

            return $e->orders()->where('status', 'pending')->first() ?? $e->orders()->create(['user_id' => $e->organizer_id, 'reference' => 'SM-'.Str::uuid(), 'amount' => $snap['price'], 'package_snapshot' => $snap]);
        });

        return redirect()->away($payments->checkout($order), 303);
    }

    public function reconcile(Order $order, PaymentService $payments)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $payments->reconcile($order);

        return back()->with('success', 'Status pembayaran diperiksa.');
    }
}
