<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * @var list<string>
     */
    protected $except = [];

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
