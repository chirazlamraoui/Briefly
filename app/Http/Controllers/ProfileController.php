<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user()->load('team'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $user->update($request->validated());

        return redirect()->route('profile.edit')->with('success', __('Profile updated successfully.'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        auth()->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()->route('profile.edit')->with('success', __('Password changed successfully.'));
    }
}
