<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Package;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || ! config('skillmatch.demo') || ! config('skillmatch.demo_password')) {
            throw new \RuntimeException('Demo hanya untuk local melalui artisan skillmatch:demo.');
        }
        $this->call(DatabaseSeeder::class);
        $people = [];
        foreach (['admin', 'organizer', 'volunteer'] as $role) {
            $email = $role.'@skillmatch.test';
            $u = User::firstOrNew(compact('email'));
            if ($u->exists) {
                $people[$role] = $u;

                continue;
            }$u->fill(['name' => ucfirst($role).' Demo', 'password' => config('skillmatch.demo_password'), 'city' => 'Malang', 'availability' => [['start' => now()->utc()->toIso8601String(), 'end' => now()->addMonths(2)->utc()->toIso8601String()]]]);
            $u->role = $role;
            $u->email_verified_at = now();
            $u->save();
            $u->skills()->sync(Skill::limit(3)->pluck('id'));
            $people[$role] = $u;
        }
        foreach (['Festival Komunitas Malang', 'Aksi Kampus Peduli', 'Ruang Kreatif Akhir Pekan'] as $i => $title) {
            $e = Event::firstOrCreate(['title' => $title, 'organizer_id' => $people['organizer']->id], ['package_id' => Package::where('name', 'Free')->first()->id, 'category' => ['Komunitas', 'Kampus', 'Hiburan'][$i], 'city' => 'Malang', 'venue' => 'Lokasi demo — bukan acara nyata', 'description' => 'Data contoh untuk mencoba rekrutmen volunteer. Bantu registrasi peserta dan koordinasi kegiatan. Event ini bukan pengumuman kegiatan nyata.', 'starts_at' => now()->addDays(10 + $i)->setTime(1, 0), 'ends_at' => now()->addDays(10 + $i)->setTime(9, 0), 'registration_opens_at' => now()->subDay(), 'deadline' => now()->addDays(5), 'moderation' => 'approved', 'publication' => 'published', 'package_snapshot' => Package::where('name', 'Free')->first()->snapshot()]);
            Position::firstOrCreate(['event_id' => $e->id, 'name' => 'Tim Registrasi'], ['quota' => 5, 'starts_at' => $e->starts_at, 'ends_at' => $e->ends_at, 'skill_ids' => Skill::limit(3)->pluck('id')->all(), 'required_skill_ids' => [], 'cv_required' => false, 'duration_minutes' => 10, 'questions' => [['text' => 'Apa tindakan pertama saat peserta tidak ada dalam daftar?', 'options' => ['Menolak tanpa penjelasan', 'Memeriksa bukti pendaftaran dan menghubungi PIC', 'Membagikan data semua peserta', 'Mengabaikan peserta'], 'answer' => 1], ['text' => 'Bagaimana memperlakukan dokumen pribadi peserta?', 'options' => ['Menyebarkannya di grup publik', 'Memberikannya ke semua pengunjung', 'Membatasi akses sesuai kebutuhan tugas', 'Mengunggahnya ke media sosial'], 'answer' => 2]]]);
        }
    }
}
