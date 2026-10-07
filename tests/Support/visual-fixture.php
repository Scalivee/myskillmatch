<?php

use App\Models\Application;
use App\Models\Attempt;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Package;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// Isolated browser fixtures only. Never connect this runner to working data.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || ! str_contains(config('database.connections.sqlite.database'), 'visual-review')) {
    throw new RuntimeException('Browser fixtures require the isolated visual-review SQLite database.');
}
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$users = [];
foreach (['admin' => 'Admin Review', 'organizer' => 'Komunitas Ruang Bersama', 'volunteer' => 'Nadia Putri', 'new' => 'Relawan Baru'] as $role => $name) {
    $user = new User(['name' => $name, 'email' => $role.'@visual.example.test', 'password' => 'VisualReview123', 'city' => $role === 'new' ? null : 'Malang', 'bio' => 'Akun sintetis untuk pemeriksaan visual lokal.', 'availability' => $role === 'new' ? [] : [['start' => now()->toIso8601String(), 'end' => now()->addMonth()->toIso8601String()]]]);
    $user->role = $role === 'new' ? 'volunteer' : $role;
    $user->email_verified_at = now();
    $user->save();
    if ($role !== 'new') {
        $user->skills()->sync(Skill::limit(3)->pluck('id'));
    }
    $users[$role] = $user;
}
$package = Package::first();
foreach (['Ruang Hijau: Bergerak Bersama untuk Kota yang Lebih Lestari', 'Festival Kreativitas dan Cerita Komunitas', 'Teman Belajar: Satu Hari Berbagi Inspirasi'] as $i => $title) {
    $event = Event::create(['organizer_id' => $users['organizer']->id, 'package_id' => $package->id, 'title' => $title, 'category' => ['Komunitas', 'Hiburan', 'Kampus'][$i], 'city' => 'Malang', 'venue' => 'Taman Komunitas, Malang', 'description' => 'Kegiatan contoh untuk pemeriksaan visual. Bersama komunitas, kita merawat lingkungan, berbagi pengalaman, dan membuka ruang belajar yang ramah untuk semua.', 'starts_at' => now()->addDays(7 + $i), 'ends_at' => now()->addDays(7 + $i)->addHours(4), 'registration_opens_at' => now()->subDay(), 'deadline' => now()->addDays(4), 'moderation' => 'approved', 'publication' => 'published', 'package_snapshot' => $package->snapshot(), 'activated_at' => now()->subDays($i)]);
    $position = Position::create(['event_id' => $event->id, 'name' => ['Koordinator Lapangan', 'Dokumentasi Kegiatan', 'Pendamping Belajar'][$i], 'quota' => 5, 'starts_at' => $event->starts_at, 'ends_at' => $event->ends_at, 'skill_ids' => [1, 2], 'required_skill_ids' => [], 'cv_required' => true, 'questions' => [['text' => 'Apa yang Anda lakukan saat peserta membutuhkan bantuan?', 'options' => ['Mendengarkan dan membantu sesuai prosedur', 'Meninggalkan lokasi', 'Mengabaikan peserta', 'Menutup kegiatan'], 'answer' => 0]], 'duration_minutes' => 15]);
    $application = Application::create(['volunteer_id' => $users['volunteer']->id, 'event_id' => $event->id, 'position_id' => $position->id, 'status' => ['assessment_pending', 'under_review', 'accepted'][$i], 'submitted_at' => now()->subDays($i), 'snapshot' => ['name' => 'Nadia Putri', 'city' => 'Malang', 'bio' => 'Senang belajar dan berkontribusi bersama komunitas.'], 'match_score' => 85, 'skill_score' => 70, 'availability_score' => 100, 'location_score' => 100]);
    if ($i === 0) {
        Attempt::create(['application_id' => $application->id, 'questions' => $position->questions, 'answers' => [], 'started_at' => now(), 'expires_at' => now()->addHours(2)]);
    }
    Notice::create(['user_id' => $users['volunteer']->id, 'message' => ['Assessment siap dikerjakan untuk Ruang Hijau.', 'Lamaran dokumentasi sedang ditinjau penyelenggara.', 'Anda diterima sebagai Pendamping Belajar. Siapkan jadwal Anda.'][$i], 'dedupe' => 'visual-'.$i]);
}
$draftEvent = Event::create(['organizer_id' => $users['organizer']->id, 'package_id' => $package->id, 'title' => 'Kegiatan Baru — Perlu Penyesuaian Jadwal dan Informasi Tempat', 'category' => 'Komunitas', 'city' => 'Malang', 'venue' => 'Ruang Komunitas', 'description' => 'Data sintetis untuk menguji catatan revisi dan daftar tugas penyelenggara.', 'starts_at' => now()->addDays(14), 'ends_at' => now()->addDays(14)->addHours(3), 'registration_opens_at' => now(), 'deadline' => now()->addDays(4), 'moderation' => 'revision_required', 'review_note' => 'Lengkapi alamat kegiatan dan jadwal setiap posisi.', 'package_snapshot' => $package->snapshot()]);
$draftPosition = Position::create(['event_id' => $draftEvent->id, 'name' => 'Pendamping kegiatan', 'quota' => 3, 'starts_at' => $draftEvent->starts_at, 'ends_at' => $draftEvent->ends_at, 'skill_ids' => [1], 'required_skill_ids' => [], 'questions' => [], 'duration_minutes' => 15]);
$draft = Application::create(['volunteer_id' => $users['volunteer']->id, 'event_id' => $draftEvent->id, 'position_id' => $draftPosition->id, 'status' => 'draft']);
Document::create(['id' => Str::uuid(), 'application_id' => $draft->id, 'uploader_id' => $users['volunteer']->id, 'kind' => 'cv', 'path' => 'visual-review/never-download.pdf', 'original_name' => 'CV contoh pemeriksaan tampilan.pdf', 'mime' => 'application/pdf', 'size' => 1024, 'checksum' => str_repeat('a', 64), 'status' => 'scan_failed']);
Order::create(['event_id' => 1, 'user_id' => $users['organizer']->id, 'reference' => 'SM-VISUAL-PENDING', 'amount' => 10000, 'package_snapshot' => array_replace($package->snapshot(), ['name' => 'Standard', 'price' => 10000]), 'status' => 'pending']);
AuditLog::create(['user_id' => $users['admin']->id, 'action' => 'visual.fixture', 'subject' => 'Isolated test data']);
echo "Visual fixtures ready (isolated SQLite).\n";
