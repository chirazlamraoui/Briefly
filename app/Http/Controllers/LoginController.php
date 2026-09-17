<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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

        $loggedIn = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $loggedIn) {
            return $this->invalidLogin($request);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = auth()->user();

        if ($request->expectsJson()) {
            return $this->phoneLogin($request, $user);
        }

        return $this->browserHome($user);
    }

    public function logout(Request $request): RedirectResponse|JsonResponse|Response
    {
        if ($request->expectsJson()) {
            $request->user()?->currentAccessToken()?->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('login');
    }

    private function invalidLogin(Request $request): RedirectResponse|JsonResponse
    {
        $error = ['email' => __('Invalid credentials.')];

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Invalid credentials.'),
                'errors' => $error,
            ], 422);
        }

        return back()->withErrors($error)->onlyInput('email');
    }

    /**
     * Phone: return a Sanctum token. The app stores it and sends it as Bearer on later requests.
     */
    private function phoneLogin(Request $request, User $user): JsonResponse
    {
        $user->load('teams');

        return response()->json([
            'token' => $user->createToken($request->input('device_name', 'mobile'))->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    private function browserHome(User $user): RedirectResponse
    {
        $home = $user->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->intended($home);
    }
}
