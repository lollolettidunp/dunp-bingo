<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->with('login_error', 'Sessione Google scaduta: riprova il login.');
        }
        $email = strtolower((string) $google->getEmail());
        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 403);

        $user = User::where('email', $email)->first();
        abort_if($user && ! $user->is_enabled, 403);

        $isWorkspace = Str::afterLast($email, '@') === strtolower((string) config('services.google.workspace_domain'));
        abort_unless($isWorkspace || $user?->is_enabled, 403);

        $user ??= User::create(['email' => $email, 'name' => $google->getName() ?: $email, 'is_enabled' => true]);
        $user->update([
            'google_id' => $google->getId(),
            'name' => $google->getName() ?: $user->name,
            'avatar_url' => $google->getAvatar(),
        ]);

        Auth::login($user, remember: true);

        return redirect()->intended(route('board'));
    }
}
