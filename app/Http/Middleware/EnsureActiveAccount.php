<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->routeIs('login', 'login.store')) {
            return $next($request);
        }

        $canReactivate = $user->isStudent()
            && $user->account_deletion_requested_at
            && ! $user->account_anonymized_at
            && now()->lt($user->account_deletion_requested_at->copy()->addDays(30));
        $message = $canReactivate
            ? 'Your account is pending deletion. Log in within 30 days to reactivate it.'
            : 'Your account has been deactivated. Please contact an administrator.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);

    }
}
