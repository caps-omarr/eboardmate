<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class OwnerSettingsController extends Controller
{
    /**
     * Display the owner's settings page.
     */
    public function edit(Request $request)
    {
        return Inertia::render('Owner/Settings', [
            'owner' => $request->user(),
        ]);
    }

    /**
     * Update the owner's profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('photo')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        unset($validated['photo']);
        $user->update($validated);

        return back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update the owner's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}