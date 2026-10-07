<?php

namespace App\Http\Controllers;

use App\Jobs\ScanDocument;
use App\Models\Application;
use App\Models\Document;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    private function editable(Application $a): void
    {
        abort_unless($a->volunteer_id === auth()->id(), 403);
        abort_unless(in_array($a->status, ['draft', 'documents_pending']) && $a->event->publication === 'published' && $a->event->lifecycle === 'upcoming' && $a->event->deadline->isFuture(), 422, 'Dokumen sudah dikunci atau masa pendaftaran berakhir.');
    }

    public function upload(Request $r, Application $application)
    {
        $this->editable($application);
        $v = $r->validate(['kind' => 'required|in:cv,support', 'file' => 'required|file|mimes:pdf,jpg,jpeg,png|extensions:pdf,jpg,jpeg,png|max:5120']);
        $file = $r->file('file');
        $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if (! in_array($actualMime, ['application/pdf', 'image/jpeg', 'image/png'])) {
            throw ValidationException::withMessages(['file' => 'Isi file tidak sesuai format yang diizinkan.']);
        }if ($v['kind'] === 'cv' && $actualMime !== 'application/pdf') {
            throw ValidationException::withMessages(['file' => 'CV harus berupa PDF.']);
        }$id = (string) Str::uuid();
        $path = $file->storeAs('quarantine', $id.'.'.$file->extension(), 'private');
        if (! $path) {
            throw ValidationException::withMessages(['file' => 'File gagal disimpan.']);
        }
        try {
            $d = DB::transaction(function () use ($application, $v, $file, $id, $path, $actualMime) {
                $a = Application::lockForUpdate()->findOrFail($application->id);
                $this->editable($a);
                if ($a->documents()->count() >= 5) {
                    throw ValidationException::withMessages(['file' => 'Maksimal lima file. Hapus file gagal/draft terlebih dahulu.']);
                }if ($a->status !== 'draft' && $v['kind'] === 'cv' && $a->documents()->where('kind', 'cv')->where('status', 'ready')->exists()) {
                    throw ValidationException::withMessages(['file' => 'CV lamaran terkirim sudah dikunci.']);
                }$d = $a->documents()->create(['id' => $id, 'uploader_id' => auth()->id(), 'kind' => $v['kind'], 'path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 180), 'mime' => $actualMime, 'size' => $file->getSize(), 'checksum' => hash_file('sha256', Storage::disk('private')->path($path))]);
                ScanDocument::dispatch($id)->afterCommit();
                Activity::log('document.uploaded', $id);

                return $d;
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Dokumen diunggah dan menunggu pemeriksaan.');
    }

    public function download(Document $document)
    {
        Gate::authorize('download', $document);
        abort_unless(Storage::disk('private')->exists($document->path), 404);
        Activity::log('document.downloaded', $document->id);

        return Storage::disk('private')->download($document->path, $document->original_name, ['Content-Type' => $document->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function destroy(Document $document)
    {
        $this->editable($document->application);
        DB::transaction(function () use ($document) {
            $a = Application::lockForUpdate()->findOrFail($document->application_id);
            $this->editable($a);
            $d = Document::lockForUpdate()->findOrFail($document->id);
            abort_unless($a->status === 'draft' || in_array($d->status, ['rejected', 'scan_failed']), 422);
            $path = $d->path;
            Activity::log('document.deleted', $d->id);
            $d->delete();
            DB::afterCommit(fn () => Storage::disk('private')->delete($path));
        });

        return back()->with('success', 'Dokumen dihapus.');
    }

    public function retry(Document $document)
    {
        $this->editable($document->application);
        abort_unless($document->status === 'scan_failed', 422);
        $document->update(['status' => 'quarantined']);
        ScanDocument::dispatch($document->id);

        return back()->with('success', 'Pemeriksaan dijadwalkan ulang.');
    }
}
