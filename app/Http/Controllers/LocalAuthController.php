<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LocalAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        abort_unless(config('integrations.auth.provider') === 'local', 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            activity()
                ->useLog('auth')
                ->causedByAnonymous()
                ->event('login_denied')
                ->withProperties([
                    'email' => $credentials['email'],
                    'reason' => 'invalid_credentials',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Login denied');

            throw ValidationException::withMessages([
                'login' => __('These credentials do not match our records.'),
            ]);
        }

        $user = Auth::user();

        if ($user->roles->isEmpty()) {
            Auth::logout();

            activity()
                ->useLog('auth')
                ->causedByAnonymous()
                ->event('login_denied')
                ->withProperties([
                    'email' => $credentials['email'],
                    'reason' => 'no_role_assigned',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('Login denied');

            throw ValidationException::withMessages([
                'login' => __('Your account has no role assigned. Contact an administrator.'),
            ]);
        }

        $request->session()->regenerate();

        activity()
            ->useLog('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->withProperties([
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Logged in');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::logout();

        activity()
            ->useLog('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('logout')
            ->withProperties([
                'email' => $user?->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Logged out');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
