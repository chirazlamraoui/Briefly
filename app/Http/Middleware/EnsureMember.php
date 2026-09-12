<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isTeamLead()) {
            return redirect()->route('team.tasks');
        }

        return $next($request);
    }
}
