<?php

use App\Http\Middleware\ValidateOidcToken;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('home'));
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'oidc' => ValidateOidcToken::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // TODO: uitcommenten als er een solide basis staat.
        // Voor nu wil ik alles zien wat er mis gaat, maar later levert dit veel ruis op
        $exceptions->dontReport([
            // AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            // TooManyRedirectsException::class,
            // ValidationException::class,
        ]);

        $exceptions->report(function (AuthorizationException $e): bool {
            activity()
                ->useLog('security')
                ->causedBy(auth()->user())
                ->event('access_denied')
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                ])
                ->log('Toegang geweigerd');

            return false;
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:cleanup-retention-data')
            ->daily()
            ->onOneServer()
            ->runInBackground();
    })
    ->create();
