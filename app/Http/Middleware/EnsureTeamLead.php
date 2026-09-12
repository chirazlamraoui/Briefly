<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamLead
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isTeamLead()) {
            abort(403, __('This action is reserved for Team Leads.'));
        }

        return $next($request);
    }
}
