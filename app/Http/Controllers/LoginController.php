<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Browser gets a cookie. Phone gets a Sanctum token in JSON. */
class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('Invalid credentials.')]);
        }

        $request->session()->regenerate();
        $user = auth()->user();

        if ($request->expectsJson()) {
            // The phone stores this token and sends it as "Bearer" on every later request.
            return response()->json([
                'token' => $user->createToken($credentials['device_name'] ?? 'mobile')->plainTextToken,
                'user' => new UserResource($user->load('teams')),
            ]);
        }

        return redirect()->intended(route($user->isAdmin() ? 'admin.dashboard' : 'dashboard'));
    }

    public function logout(Request $request): RedirectResponse|Response
    {
        if ($request->expectsJson()) {
            $request->user()?->currentAccessToken()?->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->noContent() : redirect()->route('login');
    }
}
