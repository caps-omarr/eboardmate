<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            if ($role === 'super_admin') {
                return redirect()->route('admin.login');
            }

            return redirect()->route('owner.login');
        }

        if (! $request->user()->isActive()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($role === 'super_admin') {
                return redirect()
                    ->route('admin.login')
                    ->with('error', 'Your account is inactive. Please contact the system administrator.');
            }

            return redirect()
                ->route('owner.login')
                ->with('error', 'Your account is inactive. Please contact the system administrator.');
        }

        if ($request->user()->role !== $role) {
            abort(403, 'You are not allowed to access this page.');
        }

        return $next($request);
    }
}