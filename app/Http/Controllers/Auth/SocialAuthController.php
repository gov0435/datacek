<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureProviderIsAllowed($provider);

        return Socialite::driver($provider)->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsAllowed($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('auth.no-access');
        }

        $email = strtolower((string) $socialiteUser->getEmail());

        if ($email === '') {
            return redirect()->route('auth.no-access');
        }

        $whitelist = Whitelist::query()
            ->where('email', $email)
            ->first();

        if ($whitelist === null) {
            return redirect()->route('auth.no-access');
        }

        $existingUser = User::query()
            ->where('email', $email)
            ->first();

        if ($existingUser !== null) {
            Auth::login($existingUser);
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        $providerId = $socialiteUser->getId();

        $user = User::query()->create([
            'name' => $socialiteUser->getName() ?? $whitelist->nama,
            'email' => $email,
            'instansi' => $whitelist->instansi,
            'provider' => strtolower($provider),
            'provider_id' => $providerId === null ? null : (string) $providerId,
            'avatar' => $socialiteUser->getAvatar(),
            'role' => 'member',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function ensureProviderIsAllowed(string $provider): void
    {
        $allowedDrivers = config('services.socialite.allowed_drivers', ['google']);

        abort_unless(in_array(strtolower($provider), $allowedDrivers, true), 404);
    }
}
