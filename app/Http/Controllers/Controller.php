<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\View\View;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * One action, two answers:
     * - Browser (normal visit) → HTML page or redirect.
     * - Phone (Accept: application/json) → JSON.
     *
     * @param  array<string, mixed>|JsonResource|ResourceCollection|JsonResponse  $json
     */
    protected function respond(
        Request $request,
        View|RedirectResponse $html,
        array|JsonResource|ResourceCollection|JsonResponse $json,
        int $status = 200,
    ): View|RedirectResponse|JsonResponse {
        if (! $request->expectsJson()) {
            return $html;
        }

        if ($json instanceof JsonResponse) {
            return $json;
        }

        if ($json instanceof JsonResource || $json instanceof ResourceCollection) {
            return $json->response()->setStatusCode($status);
        }

        return response()->json($json, $status);
    }
}
