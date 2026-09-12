<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureCanonicalAppUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureCanonicalAppUrlTest extends TestCase
{
    public function test_redirects_to_app_url_when_host_differs(): void
    {
        config(['app.url' => 'http://localhost:8000']);

        $request = Request::create('http://127.0.0.1:8000/projects', 'GET');

        $response = (new EnsureCanonicalAppUrl)->handle(
            $request,
            fn () => response('ok'),
        );

        $this->assertTrue($response->isRedirect('http://localhost:8000/projects'));
    }

    public function test_allows_requests_on_canonical_host(): void
    {
        config(['app.url' => 'http://localhost:8000']);

        $request = Request::create('http://localhost:8000/projects', 'GET');

        $response = (new EnsureCanonicalAppUrl)->handle(
            $request,
            fn () => response('ok', 200),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
