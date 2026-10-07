<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

// Local-only runner: never run RefreshDatabase against the configured working database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');
if (! app()->environment('local') || config('database.default') !== 'mysql' || ! in_array($connection['host'], ['localhost', '127.0.0.1', '::1'], true) || ! empty($connection['url']) || app()->configurationIsCached()) {
    fwrite(STDERR, "Requires local MySQL with uncached configuration and no DB_URL.\n");
    exit(1);
}

$tag = bin2hex(random_bytes(6));
$database = 'skillmatch_review_'.$tag.'_test';
$username = 'sm_review_'.$tag;
$password = bin2hex(random_bytes(24));
$pdo = DB::connection('mysql')->getPdo();
$createdDatabase = false;
$createdHosts = [];
$stage = 'create isolated database';
$exit = 1;

try {
    // No IF NOT EXISTS: collisions must fail, never reuse or clean up an existing database.
    $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $createdDatabase = true;
    foreach (['localhost', '127.0.0.1'] as $host) {
        $stage = 'create restricted test account';
        $account = $pdo->quote($username).'@'.$pdo->quote($host);
        $pdo->exec('CREATE USER '.$account.' IDENTIFIED BY '.$pdo->quote($password));
        $createdHosts[] = $host;
        $pdo->exec('GRANT ALL PRIVILEGES ON `'.$database.'`.* TO '.$account);
    }
    $env = [
        'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
        'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => (string) $connection['port'],
        'DB_DATABASE' => $database, 'DB_USERNAME' => $username, 'DB_PASSWORD' => $password, 'DB_URL' => '',
        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array', 'BCRYPT_ROUNDS' => '4',
    ];
    echo "Isolated MySQL suite: $database (restricted temporary account).\n";
    foreach ([['artisan', 'test', '--compact'], ['artisan', 'migrate', '--force', '--no-interaction'], ['tests/Support/concurrency.php']] as $command) {
        $stage = implode(' ', $command);
        $process = new Process(array_merge([PHP_BINARY], $command), base_path(), $env);
        $process->setTimeout(180);
        $process->run(fn ($type, $output) => print ($output));
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Test command failed.');
        }
    }
    $exit = 0;
} catch (Throwable $e) {
    // SQL statements can contain the generated password; never print the exception message.
    fwrite(STDERR, "MySQL review failed during: $stage. Exception: ".get_class($e).".\n");
} finally {
    if ($createdDatabase && preg_match('/\Askillmatch_review_[a-f0-9]{12}_test\z/', $database) && $database !== $connection['database']) {
        $pdo->exec('DROP DATABASE `'.$database.'`');
    }
    foreach ($createdHosts as $host) {
        $pdo->exec('DROP USER '.$pdo->quote($username).'@'.$pdo->quote($host));
    }
    echo "Temporary MySQL resources cleaned up. Working database unchanged.\n";
}
exit($exit);
