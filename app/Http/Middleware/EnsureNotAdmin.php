<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            if ($request->expectsJson()) {
                abort(403, __('This action is reserved for team members.'));
            }

            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
