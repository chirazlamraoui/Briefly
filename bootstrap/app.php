<?php

use App\Http\Middleware\EnsureCanonicalAppUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    // One route file for the website and the phone (no /api prefix).
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            return auth()->user()?->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard');
        });
        $middleware->web(prepend: [
            EnsureCanonicalAppUrl::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->web(replace: [
            PreventRequestForgery::class => VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Phone asked for JSON, so errors stay JSON instead of an HTML page.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );
    })->create();
