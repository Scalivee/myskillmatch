<?php

use App\Models\Application;
use App\Models\Attempt;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\MatchingService;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('skillmatch:admin', function () {
    $email = $this->ask('Email Admin');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
        $this->error('Email tidak valid atau sudah terdaftar.');

        return 1;
    }$name = $this->ask('Nama Admin');
    $password = $this->secret('Kata sandi (minimal 12 karakter, huruf besar/kecil dan angka)');
    if (strlen($password) < 12 || ! preg_match('/[A-Z]/', $password) || ! preg_match('/[a-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
        $this->error('Kata sandi belum memenuhi syarat.');

        return 1;
    }$u = new User(['name' => $name, 'email' => $email, 'password' => $password]);
    $u->role = 'admin';
    $u->email_verified_at = now();
    $u->save();
    $this->info('Admin dibuat melalui akses operator tepercaya.');
});
Artisan::command('skillmatch:demo', function () {
    if (! app()->environment('local') || ! config('skillmatch.demo')) {
        $this->error('Atur APP_ENV=local dan SKILLMATCH_DEMO=true untuk data sintetis.');

        return 1;
    }$password = $this->secret('Kata sandi akun demo baru (minimal 12 karakter)');
    if (strlen($password) < 12) {
        $this->error('Minimal 12 karakter.');

        return 1;
    }config(['skillmatch.demo_password' => $password]);
    (new DemoSeeder)->run();
    $this->info('Akun baru: admin@skillmatch.test, organizer@skillmatch.test, volunteer@skillmatch.test. Akun lama tidak ditimpa.');
});
Artisan::command('skillmatch:expire-assessments', function () {
    Attempt::whereNull('finished_at')->where('expires_at', '<=', now())->each(fn ($t) => app(AssessmentService::class)->finish($t));
    $this->info('Assessment kedaluwarsa diproses.');
});
Artisan::command('skillmatch:screen-pending', function () {
    Application::whereIn('status', ['submitted', 'documents_pending'])->each(fn ($a) => app(MatchingService::class)->screen($a));
});
Schedule::command('skillmatch:expire-assessments')->everyMinute()->withoutOverlapping();
Schedule::command('skillmatch:screen-pending')->everyFiveMinutes()->withoutOverlapping();
