<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Attempt;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function finish(Attempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $t = Attempt::lockForUpdate()->findOrFail($attempt->id);
            if ($t->finished_at) {
                return;
            }$a = Application::lockForUpdate()->findOrFail($t->application_id);
            $correct = 0;
            foreach ($t->questions as $i => $q) {
                if (isset($t->answers[$i]) && (int) $t->answers[$i] === (int) $q['answer']) {
                    $correct++;
                }
            }$score = count($t->questions) ? round(100 * $correct / count($t->questions), 2) : 0;
            $t->update(['finished_at' => now()]);
            if ($a->status === 'assessment_pending') {
                $a->update(['status' => 'under_review', 'assessment_score' => $score]);
                Activity::notify($a->event->organizer_id, 'Assessment kandidat selesai. Tinjau pelamar.', 'assessment-'.$a->id);
            }
        });
    }
}
