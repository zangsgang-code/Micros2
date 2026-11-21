<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPageAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * LoginController — signs users in with either their USERNAME or EMAIL
 * (the login Blade labels the field "Username or Email"), then
 * routes each role to its own dashboard:
 *
 *   admin → admin.dashboard · faculty → faculty.dashboard · student → dashboard
 */
class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRouteFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],   // username OR email
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['email']);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $accountReactivated = false;

        if ($user->isStudent() && $user->account_deletion_requested_at) {
            $reactivationDeadline = $user->account_deletion_requested_at->copy()->addDays(30);

            if ($user->account_anonymized_at || now()->greaterThanOrEqualTo($reactivationDeadline)) {
                return back()
                    ->withErrors(['email' => 'The 30-day reactivation period has ended. Please contact support.'])
                    ->onlyInput('email');
            }

            $user->forceFill([
                'account_deletion_requested_at' => null,
                'is_active' => true,
            ])->save();
            $accountReactivated = true;
        } elseif (! $user->is_active) {
            return back()
                ->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.'])
                ->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $redirect = redirect()->intended(route($this->dashboardRouteFor($user)));

        if ($accountReactivated) {
            $redirect->with('success', 'Your account is reactivated and the deletion request has been cancelled.');
        }

        return $redirect;
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('Homepage');
    }

    private function dashboardRouteFor(User $user): string
    {
        if ($user->isSubAdmin()) {
            return app(AdminPageAccessService::class)->firstRouteFor($user) ?? 'admin.dashboard';
        }

        return match ($user->roleName()) {
            'admin' => 'admin.dashboard',
            'faculty' => 'faculty.dashboard',
            default => 'dashboard',
        };
    }
}
