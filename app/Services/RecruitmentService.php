<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Event;
use App\Models\Position;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentService
{
    public function submit(Application $app): void
    {
        DB::transaction(function () use ($app) {
            $event = Event::lockForUpdate()->findOrFail($app->event_id);
            $a = Application::lockForUpdate()->findOrFail($app->id);
            if ($a->status !== 'draft') {
                return;
            }
            if (! $event->isOpen()) {
                throw ValidationException::withMessages(['event' => 'Pendaftaran sudah ditutup atau batas lamaran tercapai.']);
            }
            $u = $a->volunteer;
            $p = $a->position;
            if (! $u->city || ! $u->skills()->exists() || ! $u->availability) {
                throw ValidationException::withMessages(['profile' => 'Lengkapi kota, skill, dan ketersediaan pada Profil.']);
            }
            if ($p->cv_required && ! $a->documents()->where('kind', 'cv')->whereIn('status', ['ready', 'quarantined', 'scan_failed'])->exists()) {
                throw ValidationException::withMessages(['cv' => 'Unggah CV terlebih dahulu.']);
            }
            $a->update(['submitted_at' => now(), 'status' => 'submitted', 'snapshot' => ['city' => $u->city, 'name' => $u->name, 'bio' => $u->bio, 'skills' => $u->skills->pluck('id')->all(), 'availability' => $u->availability, 'rule' => '50-30-20-v1']]);
            app(MatchingService::class)->screen($a->fresh());
            Activity::notify($event->organizer_id, 'Lamaran baru untuk '.$event->title, 'submitted-'.$a->id);
            Activity::log('application.submitted', (string) $a->id);
        });
    }

    public function decide(Application $app, string $decision, string $reason): void
    {
        DB::transaction(function () use ($app, $decision, $reason) {
            User::lockForUpdate()->findOrFail($app->volunteer_id);
            $p = Position::lockForUpdate()->findOrFail($app->position_id);
            $a = Application::lockForUpdate()->findOrFail($app->id);
            if ($a->status !== 'under_review') {
                throw ValidationException::withMessages(['status' => 'Kandidat belum siap ditinjau atau sudah diputuskan.']);
            }
            if ($a->event->lifecycle !== 'upcoming' || $a->event->starts_at->isPast()) {
                throw ValidationException::withMessages(['event' => 'Seleksi hanya tersedia sebelum event dimulai.']);
            }
            if ($decision === 'accepted') {
                if ($p->applications()->where('status', 'accepted')->count() >= $p->quota) {
                    throw ValidationException::withMessages(['quota' => 'Kuota posisi sudah penuh.']);
                }
                $conflict = Application::where('volunteer_id', $a->volunteer_id)->where('status', 'accepted')->whereHas('event', fn ($q) => $q->where('lifecycle', '!=', 'cancelled'))->whereHas('position', fn ($q) => $q->where('starts_at', '<', $p->ends_at)->where('ends_at', '>', $p->starts_at))->exists();
                if ($conflict) {
                    throw ValidationException::withMessages(['schedule' => 'Volunteer sudah diterima pada jadwal yang bertumpang tindih.']);
                }
            }
            $a->update(['status' => $decision, 'decision_note' => $reason]);
            Activity::log('application.'.$decision, (string) $a->id);
            Activity::notify($a->volunteer_id, 'Hasil seleksi '.$a->event->title.': '.($decision === 'accepted' ? 'Diterima' : 'Ditolak'), 'decision-'.$a->id);
        });
    }
}
