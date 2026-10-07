<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Skill;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\PaymentService;
use App\Support\Activity;
use App\Support\Catalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard(Request $r, AnalyticsService $analytics)
    {
        $v = $r->validate(['period' => 'nullable|in:7,30,custom', 'start' => 'required_if:period,custom|nullable|date_format:Y-m-d', 'end' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:start']);
        $end = ($v['period'] ?? '30') === 'custom' ? CarbonImmutable::parse($v['end'], 'Asia/Jakarta') : CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $start = ($v['period'] ?? '30') === 'custom' ? CarbonImmutable::parse($v['start'], 'Asia/Jakarta') : $end->subDays((int) ($v['period'] ?? 30) - 1);
        if ($start->diffInDays($end) > 365) {
            throw ValidationException::withMessages(['end' => 'Rentang maksimum 366 hari.']);
        }
        try {
            $report = $analytics->report($start, $end);
        } catch (QueryException $e) {
            report($e);

            return response()->view('admin.dashboard', compact('start', 'end') + ['loadError' => true], 503);
        }

        return view('admin.dashboard', compact('start', 'end', 'report'));
    }

    public function account()
    {
        return view('admin.account');
    }

    public function saveAccount(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:100', 'current_password' => 'required|current_password:admin', 'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()]]);
        $r->user()->update(collect($v)->only(['name', 'password'])->filter()->all());
        Activity::log('admin.account.updated', (string) $r->user()->id);
        $r->session()->regenerate(true);

        return back()->with('success', 'Akun Admin diperbarui.');
    }

    public function events(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:draft,pending_review,approved,revision_required,suspended']);
        $query = Event::with('organizer', 'positions', 'package')->when($r->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$r->q.'%'));
        if ($r->filled('status')) {
            $query->where($r->status === 'suspended' ? 'publication' : 'moderation', $r->status);
        }

        return view('admin.events', ['events' => $query->latest()->paginate(10)->withQueryString()]);
    }

    public function review(Request $r, Event $event)
    {
        $v = $r->validate(['decision' => 'required|in:approved,revision_required', 'note' => 'required_if:decision,revision_required|nullable|string|max:1000']);
        DB::transaction(function () use ($event, $v) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            abort_unless($e->moderation === 'pending_review', 422);
            $e->update(['moderation' => $v['decision'], 'review_note' => $v['note'] ?? null]);
            if ($v['decision'] === 'approved' && ($e->limits()['price'] ?? null) === 0 && $e->deadline->isFuture() && $e->lifecycle === 'upcoming' && $e->publication !== 'suspended') {
                $e->update(['publication' => 'published', 'activated_at' => $e->activated_at ?? now()]);
            }Activity::log('event.reviewed', (string) $e->id, ['decision' => $v['decision']]);
            Activity::notify($e->organizer_id, 'Event '.$e->title.': '.$v['decision'], 'review-'.$e->id.'-'.$e->updated_at->timestamp);
        });

        return back()->with('success', 'Hasil verifikasi disimpan.');
    }

    public function publication(Request $r, Event $event)
    {
        $v = $r->validate(['action' => 'required|in:suspend,restore', 'reason' => 'required|string|max:500']);
        DB::transaction(function () use ($event, $v) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            if ($v['action'] === 'restore') {
                abort_unless($e->publication === 'suspended' && $e->moderation === 'approved' && ! in_array($e->lifecycle, ['cancelled', 'completed']) && $e->ends_at->isFuture(), 422);
                $paid = $e->orders()->where('status', 'paid')->whereNotNull('paid_at')->first();
                abort_unless(($e->limits()['price'] ?? null) === 0 || $paid, 422);
                $e->update(['publication' => 'published', 'activated_at' => $e->activated_at ?? now(), 'moderation_note' => $v['reason']]);
                if ($paid && ! $paid->activated_at) {
                    $paid->update(['activated_at' => now()]);
                }
            } else {
                abort_if($e->publication === 'suspended', 422);
                $e->update(['publication' => 'suspended', 'moderation_note' => $v['reason']]);
            }
            Activity::log('event.'.$v['action'], (string) $e->id, ['reason' => $v['reason']]);
            Activity::notify($e->organizer_id, 'Publikasi '.$e->title.': '.$v['reason'], 'publication-'.$e->id.'-'.Str::uuid());
        });

        return back()->with('success', 'Status publikasi diperbarui.');
    }

    public function users(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|in:volunteer,organizer,admin', 'active' => 'nullable|in:0,1']);

        return view('admin.users', ['users' => User::when($r->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$r->q.'%')->orWhere('email', 'like', '%'.$r->q.'%')))->when($r->filled('role'), fn ($q) => $q->where('role', $r->role))->when($r->filled('active'), fn ($q) => $q->where('active', $r->active))->latest()->paginate(20)->withQueryString()]);
    }

    public function toggle(Request $r, User $user)
    {
        abort_if($user->id === auth()->id() || $user->role === 'admin', 422);
        $r->validate(['reason' => 'required|string|max:500']);
        DB::transaction(function () use ($user, $r) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->forceFill(['active' => ! $user->active])->save();
            Activity::log('user.status', (string) $user->id, ['active' => $user->active, 'reason' => $r->reason]);
        });

        return back();
    }

    public function packages()
    {
        return view('admin.packages', ['packages' => Package::all(), 'skills' => Skill::orderBy('name')->get()]);
    }

    public function savePackage(Request $r, Package $package)
    {
        $v = $r->validate(['price' => 'nullable|integer|min:0|max:100000000', 'positions_limit' => 'required|integer|min:1|max:100', 'applications_limit' => 'required|integer|min:1|max:10000', 'days_limit' => 'required|integer|min:1|max:365', 'active' => 'required|boolean', 'reason' => 'required|string|max:500']);
        DB::transaction(function () use ($package, $v) {
            $package->update(collect($v)->except('reason')->all());
            Activity::log('package.updated', (string) $package->id, ['reason' => $v['reason']]);
        });

        return back()->with('success', 'Paket diperbarui untuk pengajuan baru. Order/event lama memakai snapshot.');
    }

    public function addSkill(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:80|unique:skills']);
        Skill::create($v);
        Activity::log('skill.created', $v['name']);

        return back();
    }

    public function logs()
    {
        return view('admin.logs', ['logs' => AuditLog::latest()->paginate(30), 'orders' => Order::with('event')->latest()->take(20)->get(), 'failedScans' => Document::where('status', 'scan_failed')->count(), 'failedJobs' => DB::table('failed_jobs')->count()]);
    }

    public function transactions(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:pending,paid,failed,expired']);

        return view('admin.transactions', ['orders' => Order::with('event', 'user')->when($r->filled('q'), fn ($q) => $q->where('reference', 'like', '%'.$r->q.'%'))->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))->latest()->paginate(20)->withQueryString()]);
    }

    public function reconcile(Order $order, PaymentService $payments)
    {
        Activity::log('payment.reconcile.requested', (string) $order->id);
        $payments->reconcile($order);
        Activity::log('payment.reconcile.completed', (string) $order->id, ['status' => $order->fresh()->status]);

        return back()->with('success', 'Status diperiksa melalui gateway sandbox.');
    }

    public function master()
    {
        return view('admin.master', ['groups' => collect(['categories' => 'Kategori', 'skills' => 'Skill', 'cities' => 'Kota'])->map(fn ($label, $table) => ['label' => $label, 'items' => DB::table($table)->orderBy('name')->get()])]);
    }

    public function saveMaster(Request $r)
    {
        $v = $r->validate(['table' => 'required|in:categories,skills,cities', 'id' => 'nullable|integer', 'name' => 'required|string|max:80', 'active' => 'required|boolean', 'reason' => 'required|string|max:500']);
        $r->validate(['name' => [Rule::unique($v['table'], 'name')->ignore($v['id'] ?? null)]]);
        DB::transaction(function () use ($v) {
            if (! empty($v['id'])) {
                $old = DB::table($v['table'])->where('id', $v['id'])->lockForUpdate()->first();
                abort_unless($old, 404);
                // Names are historical labels for cities/categories; retire and add instead of rewriting.
                abort_if($old->name !== $v['name'], 422, 'Nonaktifkan entri lama dan tambahkan nama baru untuk menjaga riwayat.');
                DB::table($v['table'])->where('id', $v['id'])->update(['active' => $v['active'], 'updated_at' => now()]);
            } else {
                DB::table($v['table'])->insert(['name' => $v['name'], 'active' => $v['active'], 'created_at' => now(), 'updated_at' => now()]);
            }
            Activity::log('master.updated', $v['table'].':'.$v['name'], ['active' => $v['active'], 'reason' => $v['reason']]);
        });

        return back()->with('success', 'Master data disimpan; riwayat tetap tersedia.');
    }

    public function settings()
    {
        return view('admin.settings', ['content' => Catalog::content()]);
    }

    public function saveSettings(Request $r)
    {
        $v = $r->validate(['intro' => 'nullable|string|max:500', 'announcement' => 'nullable|string|max:500', 'contact' => 'nullable|email|max:190', 'service_status' => 'required|in:normal,maintenance', 'faq_question' => 'nullable|string|max:200', 'faq_answer' => 'required_with:faq_question|nullable|string|max:1500', 'reason' => 'required|string|max:500']);
        DB::transaction(function () use ($v) {
            DB::table('settings')->updateOrInsert(['key' => 'public'], ['value' => json_encode(collect($v)->except('reason')->all()), 'updated_at' => now(), 'created_at' => now()]);
            Activity::log('settings.updated', 'public', ['reason' => $v['reason']]);
        });

        return back()->with('success', 'Konten dan informasi status layanan disimpan.');
    }
}
