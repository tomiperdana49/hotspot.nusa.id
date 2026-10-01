<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Locks an email + IP pair out after a few failed logins, so a password
 * can't be guessed by brute force. The login routes also carry a per-IP
 * throttle that covers guessing across many emails.
 */
trait ThrottlesLogins
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    private function loginThrottleKey(Request $request, string $guard): string
    {
        return $guard.'|'.Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();
    }

    private function ensureNotLockedOut(Request $request, string $guard): void
    {
        $key = $this->loginThrottleKey($request, $guard);

        if (! RateLimiter::tooManyAttempts($key, self::LOGIN_MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
        ]);
    }

    private function recordFailedLogin(Request $request, string $guard): void
    {
        RateLimiter::hit($this->loginThrottleKey($request, $guard), self::LOGIN_DECAY_SECONDS);
    }

    private function clearFailedLogins(Request $request, string $guard): void
    {
        RateLimiter::clear($this->loginThrottleKey($request, $guard));
    }
}
