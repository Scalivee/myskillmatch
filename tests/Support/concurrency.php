<?php

use App\Models\Application;
use App\Models\Event;
use App\Models\Package;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use App\Services\RecruitmentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

// Run ONLY against a disposable MySQL database ending in _test.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    fwrite(STDERR, "Requires APP_ENV=testing and a dedicated MySQL *_test database.\n");
    exit(1);
}

if (($argv[1] ?? '') === 'worker') {
    $id = (int) $argv[2];
    $barrier = $argv[3];
    file_put_contents($barrier.'-'.$id, 'ready');
    $until = microtime(true) + 10;
    while (! file_exists($barrier)) {
        if (microtime(true) > $until) {
            exit(2);
        }usleep(10000);
    }
    try {
        app(RecruitmentService::class)->decide(Application::findOrFail($id), 'accepted', '');
        echo 'accepted';
        exit(0);
    } catch (ValidationException $e) {
        echo 'quota_rejected';
        exit(42);
    }
}
(new DatabaseSeeder)->run();
$tag = Str::random(10);
$org = User::factory()->create(['role' => 'organizer', 'email' => "org-$tag@example.test"]);
$users = collect([1, 2])->map(fn ($i) => User::factory()->create(['role' => 'volunteer', 'email' => "v$i-$tag@example.test"]));
$event = Event::create(['organizer_id' => $org->id, 'package_id' => Package::first()->id, 'title' => 'Concurrent quota test', 'category' => 'Kampus', 'city' => 'Malang', 'venue' => 'Test', 'description' => 'Test only', 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHour(), 'registration_opens_at' => now()->subDay(), 'deadline' => now()->addDay(), 'moderation' => 'approved', 'publication' => 'published']);
$position = Position::create(['event_id' => $event->id, 'name' => 'One slot', 'quota' => 1, 'starts_at' => $event->starts_at, 'ends_at' => $event->ends_at, 'skill_ids' => [Skill::first()->id], 'required_skill_ids' => [], 'questions' => []]);
$apps = $users->map(fn ($u) => Application::create(['event_id' => $event->id, 'position_id' => $position->id, 'volunteer_id' => $u->id, 'status' => 'under_review', 'submitted_at' => now()]));
$barrier = storage_path('framework/concurrency-'.$tag);
$workers = [];
try {
    foreach ($apps as $a) {
        $p = new Process([PHP_BINARY, __FILE__, 'worker', (string) $a->id, $barrier], base_path());
        $p->setTimeout(20);
        $p->start();
        $workers[] = $p;
    }
    $until = microtime(true) + 10;
    while (! file_exists($barrier.'-'.$apps[0]->id) || ! file_exists($barrier.'-'.$apps[1]->id)) {
        if (microtime(true) > $until) {
            throw new RuntimeException('Worker readiness timeout');
        }usleep(10000);
    }
    file_put_contents($barrier, 'go');
    $codes = [];
    foreach ($workers as $p) {
        $p->wait();
        $codes[] = $p->getExitCode();
    }
    sort($codes);
    $accepted = Application::where('position_id', $position->id)->where('status', 'accepted')->count();
    if ($accepted !== 1 || $codes !== [0, 42]) {
        throw new RuntimeException('Expected exactly one accepted. Exit codes: '.json_encode($codes));
    }
    echo "PASS: two concurrent requests, one accepted, one quota rejection.\n";
} finally {
    foreach ($workers as $p) {
        if ($p->isRunning()) {
            $p->stop();
        }
    }
    foreach ($apps as $a) {
        @unlink($barrier.'-'.$a->id);
        $a->delete();
    }@unlink($barrier);
    $position->delete();
    $event->delete();
    foreach ($users as $u) {
        $u->delete();
    }$org->delete();
}
