<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware
            ->web(append: [
                \App\Http\Middleware\HandleInertiaRequests::class,
                \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ])
            ->statefulApi()
            ->alias([
                'single.session' => \App\Http\Middleware\EnsureSingleSession::class,
            ]);

        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reservations:mark-no-show')->everyFiveMinutes();
        $schedule->command('configuration:update-active-promo')->daily();
        $schedule->command('configuration:update-product-discount')->daily();
        // $schedule->command('keepitems:check-expiration')->daily();
        $schedule->command('general:clear-notification')->daily();
        $schedule->command('configuration:update-employee-incentives')->daily();
        $schedule->command('activitylog:clean')->daily();
        $schedule->command('configurations:set-bill-discount-active')->everyTwoMinutes();
        // $schedule->command('fetch:latest-status-submission')->everyFifteenMinutes();
        $schedule->command('waiters:auto-checkout')->dailyAt('06:00');
        $schedule->command('customers:expire-points')->daily();
        $schedule->command('customers:update-tier')->yearly();
        $schedule->command('tables:reset-locked-tables')->everyFiveSeconds();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (in_array($response->getStatusCode(), [400, 401, 403, 404, 408, 429, 502, 503])) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            } elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }
    
            return $response;
        });
    })
    ->create();
