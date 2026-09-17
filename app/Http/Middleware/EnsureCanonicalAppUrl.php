<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanonicalAppUrl
{
    /**
     * Keep the public website on APP_URL.
     * Skip the phone: it often uses a LAN IP (e.g. 10.x.x.x) instead of localhost.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson() || $request->bearerToken()) {
            return $next($request);
        }

        $parts = parse_url((string) config('app.url'));

        if (! is_array($parts) || empty($parts['host'])) {
            return $next($request);
        }

        $expectedHost = $parts['host'];
        $expectedPort = isset($parts['port']) ? (int) $parts['port'] : null;
        $expectedScheme = $parts['scheme'] ?? $request->getScheme();

        $hostMatches = strcasecmp($request->getHost(), $expectedHost) === 0;
        $portMatches = $expectedPort === null || $request->getPort() === $expectedPort;

        if ($hostMatches && $portMatches) {
            return $next($request);
        }

        $canonical = $expectedScheme.'://'.$expectedHost;

        if ($expectedPort !== null) {
            $canonical .= ':'.$expectedPort;
        }

        return redirect()->to($canonical.$request->getRequestUri(), 301);
    }
}
