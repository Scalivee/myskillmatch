<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Document;
use App\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ScanDocument implements ShouldQueue
{
    use Queueable;

    public $tries = 2;

    public $timeout = 100;

    public function __construct(public string $documentId) {}

    public function handle(): void
    {
        $d = Document::find($this->documentId);
        if (! $d || $d->status !== 'quarantined') {
            return;
        }
        $result = 'scan_failed';
        try {
            if (config('skillmatch.scanner') === 'clamav') {
                $p = new Process([config('skillmatch.clamav_binary'), '--no-summary', '--alert-encrypted=yes', Storage::disk('private')->path($d->path)]);
                $p->setTimeout(75);
                $p->run();
                $result = match ($p->getExitCode()) {
                    0 => 'ready',1 => 'rejected',default => 'scan_failed'
                };
            }
        } catch (\Throwable $e) {
            report($e);
        }
        DB::transaction(function () use ($result) {
            $initial = Document::find($this->documentId);
            if (! $initial) {
                return;
            }$app = Application::lockForUpdate()->find($initial->application_id);
            $doc = Document::lockForUpdate()->find($this->documentId);
            if (! $doc || $doc->status !== 'quarantined') {
                return;
            }$doc->update(['status' => $result]);
            app(MatchingService::class)->screen($app);
        });
    }
}
