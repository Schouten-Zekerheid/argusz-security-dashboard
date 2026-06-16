<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class AzureAuthController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless($provider === 'azure' && config('integrations.auth.provider') === 'azure', 404);

        return $this->azureDriver()->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless($provider === 'azure' && config('integrations.auth.provider') === 'azure', 404);

        $azureUser = $this->azureDriver()->user();

        $existing = User::where('email', $azureUser->getEmail())->first();

        if (! $existing || $existing->roles->isEmpty()) {
            activity()
                ->useLog('auth')
                ->causedByAnonymous()
                ->event('login_denied')
                ->withProperties([
                    'email' => $azureUser->getEmail(),
                    'name' => $azureUser->getName(),
                    'reason' => $existing ? 'no_role_assigned' : 'user_not_found',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Login denied');

            $message = 'Access denied. Please contact an administrator '
                .'to request access.';

            return redirect()->route('home')
                ->withErrors(['login' => $message]);
        }

        activity()
            ->useLog('auth')
            ->causedBy($existing)
            ->performedOn($existing)
            ->event('login')
            ->withProperties([
                'email' => $existing->email,
                'role' => $existing->getRoleNames()->first(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Logged in via Azure');

        Auth::login($existing);

        $azureName = $azureUser->getName();
        session(['azure_user_name' => $azureName ?? $existing->name]);

        return redirect()->intended(route('dashboard'));
    }

    /** @return AbstractProvider */
    private function azureDriver()
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('azure');

        return $driver->redirectUrl((string) config('services.azure.redirect'));
    }
}
