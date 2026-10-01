<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    use ThrottlesLogins;

    public function create()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureNotLockedOut($request, 'admin');

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $this->recordFailedLogin($request, 'admin');

            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $this->clearFailedLogins($request, 'admin');

        if (! Auth::guard('admin')->user()->is_active) {
            Auth::guard('admin')->logout();

            return back()->withErrors(['email' => 'Akun ini dinonaktifkan.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
