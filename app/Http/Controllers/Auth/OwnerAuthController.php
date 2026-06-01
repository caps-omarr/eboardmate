<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OwnerAuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isOwner()) {
                return redirect()->route('owner.dashboard');
            }

            if (Auth::user()->isSuperAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return Inertia::render('Auth/OwnerLogin');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $loginSuccess = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => User::ROLE_OWNER,
            'status' => User::STATUS_ACTIVE,
        ], $remember);

        if (! $loginSuccess) {
            return back()
                ->withErrors([
                    'email' => 'Invalid owner login credentials.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('owner.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('owner.login')
            ->with('success', 'You have been logged out successfully.');
    }
}