<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientLoginController extends Controller
{
    use ThrottlesLogins;

    public function create()
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('client.dashboard');
        }

        return view('auth.client-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureNotLockedOut($request, 'client');

        if (! Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            $this->recordFailedLogin($request, 'client');

            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $this->clearFailedLogins($request, 'client');

        $user = Auth::guard('client')->user();

        if (! $user->is_active || ! $user->client || ! $user->client->isActive()) {
            Auth::guard('client')->logout();

            return back()->withErrors(['email' => 'Akun atau langganan client ini tidak aktif.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        return redirect()->intended(route('client.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
