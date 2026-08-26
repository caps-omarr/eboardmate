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
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        if ($request->hasFile('photo')) {
            $uploadedFile = $request->file('photo');
            $targetDisk = config('filesystems.default') === 'cloudinary' ? 'cloudinary' : 'public';

            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk($targetDisk)->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk($targetDisk)->delete($user->avatar);
            }

            $tempPath = sys_get_temp_dir() . '/' . uniqid('ebm_avatar_', true) . '.jpg';
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($uploadedFile->getRealPath());
            $image->cover(width: 400, height: 400);
            $image->toJpeg(85)->save($tempPath);

            $fileName = 'avatars/' . \Illuminate\Support\Str::uuid() . '.jpg';

            \Illuminate\Support\Facades\Storage::disk($targetDisk)->putFileAs(
                dirname($fileName),
                new \Illuminate\Http\File($tempPath),
                basename($fileName)
            );

            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }

            $user->avatar = $fileName;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

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