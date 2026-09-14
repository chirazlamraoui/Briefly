<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View|JsonResponse
    {
        $user = auth()->user()->load(['team', 'teams']);

        return $this->respond($request, view('profile.edit', [
            'user' => $user,
        ]), [
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $user->update($request->validated());
        $user->load(['team', 'teams']);

        return $this->respond($request, redirect()->route('profile.edit')->with('success', __('Profile updated successfully.')), [
            'user' => (new UserResource($user))->resolve(),
            'message' => __('Profile updated successfully.'),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse|JsonResponse
    {
        auth()->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return $this->respond($request, redirect()->route('profile.edit')->with('success', __('Password changed successfully.')), [
            'message' => __('Password changed successfully.'),
        ]);
    }
}
