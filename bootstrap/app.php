<?php

use App\Http\Middleware\AdminPanel;
use App\Http\Middleware\Role;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $m): void {
        $m->alias(['role' => Role::class, 'admin.panel' => AdminPanel::class]);
        $m->prependToPriorityList(SubstituteBindings::class, AdminPanel::class);
        $m->redirectUsersTo(fn () => route('dashboard'));
        $m->web(append: [SecurityHeaders::class]);
        $m->validateCsrfTokens(except: ['payments/midtrans/notification']);
    })
    ->withExceptions(function (Exceptions $e): void {})
    ->create();
