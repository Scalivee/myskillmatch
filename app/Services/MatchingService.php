<?php

namespace App\Services;

use App\Models\Application;
use App\Support\Activity;
use Carbon\Carbon;

class MatchingService
{
    public function scores(array $snapshot, array $position, string $city): array
    {
        $skills = array_unique($position['skill_ids']);
        $s = count($skills) ? 100 * count(array_intersect($skills, $snapshot['skills'])) / count($skills) : 0;
        $start = Carbon::parse($position['starts_at'])->timestamp;
        $end = Carbon::parse($position['ends_at'])->timestamp;
        $intervals = [];
        foreach ($snapshot['availability'] ?? [] as $slot) {
            $a = max($start, Carbon::parse($slot['start'])->timestamp);
            $b = min($end, Carbon::parse($slot['end'])->timestamp);
            if ($b > $a) {
                $intervals[] = [$a, $b];
            }
        }usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $covered = 0;
        $lastStart = null;
        $lastEnd = null;
        foreach ($intervals as [$a,$b]) {
            if ($lastStart === null) {
                $lastStart = $a;
                $lastEnd = $b;
            } elseif ($a <= $lastEnd) {
                $lastEnd = max($lastEnd, $b);
            } else {
                $covered += $lastEnd - $lastStart;
                $lastStart = $a;
                $lastEnd = $b;
            }
        }if ($lastStart !== null) {
            $covered += $lastEnd - $lastStart;
        }
        $a = $end > $start ? min(100, 100 * $covered / ($end - $start)) : 0;
        $l = mb_strtolower(trim($snapshot['city'])) === mb_strtolower(trim($city)) ? 100 : 0;

        return ['skill_score' => round($s, 2), 'availability_score' => round($a, 2), 'location_score' => $l, 'match_score' => round(.5 * $s + .3 * $a + .2 * $l, 2)];
    }

    public function screen(Application $app): void
    {
        if (! in_array($app->status, ['submitted', 'documents_pending'])) {
            return;
        }
        $p = $app->position;
        $docs = $app->documents;
        if ($docs->whereIn('status', ['quarantined', 'scan_failed'])->isNotEmpty()) {
            $app->update(['status' => 'documents_pending']);

            return;
        }
        $fail = [];
        if ($p->cv_required && ! $docs->contains(fn ($d) => $d->kind === 'cv' && $d->status === 'ready')) {
            if ($app->event->deadline->isFuture()) {
                $app->update(['status' => 'documents_pending']);

                return;
            }
            $fail[] = 'CV wajib belum lolos pemeriksaan.';
        }
        $snap = $app->snapshot;
        $scores = $this->scores($snap, $p->attributesToArray(), $app->event->city);
        if (array_diff($p->required_skill_ids, $snap['skills'])) {
            $fail[] = 'Skill wajib belum terpenuhi.';
        }
        if ($p->full_availability && $scores['availability_score'] < 100) {
            $fail[] = 'Jadwal wajib belum sepenuhnya tersedia.';
        }
        if ($p->same_city && $scores['location_score'] < 100) {
            $fail[] = 'Kota tidak memenuhi persyaratan wajib.';
        }
        $app->update($scores + ['screening' => $fail, 'status' => $fail ? 'screening_failed' : 'assessment_pending']);
        Activity::notify($app->volunteer_id, $fail ? 'Lamaran tidak lolos screening. Lihat alasannya.' : 'Screening selesai. Silakan ikuti assessment.', 'screen-'.$app->id);
    }
}
