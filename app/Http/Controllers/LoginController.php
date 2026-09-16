<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $error = ['email' => __('Invalid credentials.')];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Invalid credentials.'),
                    'errors' => $error,
                ], 422);
            }

            return back()->withErrors($error)->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = auth()->user();

        if ($request->expectsJson()) {
            $user->load('teams');

            return response()->json([
                'token' => $user->createToken($request->input('device_name', 'mobile'))->plainTextToken,
                'user' => new UserResource($user),
            ]);
        }

        $home = $user->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->intended($home);
    }

    public function logout(Request $request): RedirectResponse|JsonResponse|Response
    {
        if ($request->expectsJson()) {
            $request->user()?->currentAccessToken()?->delete();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->noContent();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
