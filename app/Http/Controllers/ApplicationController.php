<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Attempt;
use App\Models\Event;
use App\Models\Position;
use App\Services\AssessmentService;
use App\Services\RecruitmentService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(Request $r)
    {
        return view('applications', ['applications' => $r->user()->applications()->with('event', 'position')->latest()->paginate(15)]);
    }

    public function draft(Request $r, Position $position)
    {
        $a = DB::transaction(function () use ($position, $r) {
            $event = Event::lockForUpdate()->findOrFail($position->event_id);
            abort_unless($event->isOpen(), 422, 'Pendaftaran tidak tersedia.');

            return Application::firstOrCreate(['volunteer_id' => $r->user()->id, 'event_id' => $event->id], ['position_id' => $position->id]);
        });

        return redirect()->route('application.show', $a);
    }

    public function show(Application $application)
    {
        Gate::authorize('view-application', $application);

        return view('application', ['application' => $application->load('event', 'position', 'documents', 'attempt', 'volunteer')]);
    }

    public function submit(Application $application, RecruitmentService $service)
    {
        abort_unless($application->volunteer_id === auth()->id(), 403);
        $service->submit($application);

        return back()->with('success', 'Lamaran tersimpan. Lihat status dan tindakan berikutnya.');
    }

    public function withdraw(Application $application)
    {
        abort_unless($application->volunteer_id === auth()->id(), 403);
        DB::transaction(function () use ($application) {
            $a = Application::lockForUpdate()->findOrFail($application->id);
            abort_unless(in_array($a->status, ['draft', 'documents_pending', 'assessment_pending', 'under_review', 'submitted']), 422);
            $a->update(['status' => 'withdrawn']);
            Activity::log('application.withdrawn', (string) $a->id);
        });

        return back()->with('success', 'Lamaran ditarik.');
    }

    public function start(Application $application)
    {
        abort_unless($application->volunteer_id === auth()->id(), 403);
        DB::transaction(function () use ($application) {
            $a = Application::lockForUpdate()->findOrFail($application->id);
            abort_unless($a->status === 'assessment_pending' && $a->event->lifecycle === 'upcoming' && $a->event->starts_at->isFuture(), 422);
            Attempt::firstOrCreate(['application_id' => $a->id], ['questions' => $a->position->questions, 'answers' => [], 'started_at' => now(), 'expires_at' => now()->addMinutes($a->position->duration_minutes)]);
        });

        return redirect()->route('assessment.show', $application);
    }

    public function assessment(Application $application, AssessmentService $service)
    {
        abort_unless($application->volunteer_id === auth()->id(), 403);
        $t = $application->attempt;
        abort_unless($t, 404);
        if (! $t->finished_at && $t->expires_at->isPast()) {
            $service->finish($t);
        }if ($t->fresh()->finished_at) {
            return redirect()->route('application.show', $application);
        }abort_unless($application->status === 'assessment_pending', 422);
        $questions = collect($t->questions)->map(fn ($q) => ['text' => $q['text'], 'options' => $q['options']])->all();

        return view('assessment', ['application' => $application, 'attempt' => $t, 'questions' => $questions]);
    }

    public function answer(Request $r, Application $application, AssessmentService $service)
    {
        abort_unless($application->volunteer_id === auth()->id(), 403);
        $v = $r->validate(['answers' => 'nullable|array', 'answers.*' => 'nullable|integer|between:0,3', 'finish' => 'nullable|boolean']);
        $expired = false;
        DB::transaction(function () use ($application, $v, $service, &$expired) {
            $t = Attempt::where('application_id', $application->id)->lockForUpdate()->firstOrFail();
            if ($t->finished_at) {
                return;
            }abort_unless($application->fresh()->status === 'assessment_pending', 422);
            $expired = $t->expires_at->isPast();
            if (! $expired) {
                $answers = $t->answers ?? [];
                foreach ($v['answers'] ?? [] as $i => $a) {
                    if (isset($t->questions[$i]) && $a !== null) {
                        $answers[$i] = (int) $a;
                    }
                }$t->update(['answers' => $answers]);
            }if ($expired || ($v['finish'] ?? false)) {
                $service->finish($t);
            }
        });

        return redirect()->route(($expired || ($v['finish'] ?? false)) ? 'application.show' : 'assessment.show', $application)->with('success', $expired ? 'Waktu selesai; jawaban tersimpan sebelumnya dinilai.' : 'Jawaban berhasil disimpan.');
    }

    public function history(Request $r)
    {
        return view('history', ['applications' => $r->user()->applications()->with('event', 'position')->where('status', 'accepted')->whereHas('event', fn ($q) => $q->where('lifecycle', 'completed'))->latest()->paginate(15)]);
    }
}
