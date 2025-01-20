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
            ->statefulApi();

        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reservations:mark-no-show')->dailyAt('00:00');
        $schedule->command('configuration:update-active-promo')->dailyAt('00:00');
        $schedule->command('configuration:update-product-discount')->dailyAt('00:00');
        $schedule->command('keepitems:check-expiration')->dailyAt('00:00');
        $schedule->command('general:clear-notification')->dailyAt('00:00');
        $schedule->command('configuration:update-employee-incentives')->dailyAt('17:34');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (in_array($response->getStatusCode(), [400, 401, 403, 404, 408, 429, 500, 502, 503])) {
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
