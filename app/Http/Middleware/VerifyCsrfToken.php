<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * @var list<string>
     */
    protected $except = [];

    /**
     * The website sends a hidden CSRF field. The phone cannot.
     * Skip CSRF when the phone already proved itself with a Bearer token,
     * or when it is posting JSON to login / password reset.
     */
    protected function inExceptArray($request): bool
    {
        if ($request->bearerToken()) {
            return true;
        }

        if ($request->expectsJson() && $request->is('login', 'forgot-password', 'reset-password')) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
