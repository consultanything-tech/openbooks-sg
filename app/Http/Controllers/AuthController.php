<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showLogin()
    {
        return $this->showLoginForm();
    }

    public function login(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        $request->merge([
            'email' => $email,
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Attempt 1: Standard Laravel Auth
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $this->rememberIntendedUrl($request);
            // Check if 2FA is enabled
            $user = Auth::user();
            if ($user->two_factor_enabled) {
                Auth::logout();
                session(['2fa_user_id' => $user->id]);

                return redirect()->route('2fa.challenge');
            }
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Attempt 2: Direct model check (in case of double-hash or trim discrepancy)
        $user = User::where('email', $email)->first();
        if ($user) {
            $passwordMatches = Hash::check($password, $user->password)
                || password_verify($password, $user->password);

            if ($passwordMatches) {
                // Ensure password hash is clean
                if (! Hash::check($password, $user->password)) {
                    $user->password = Hash::make($password);
                    $user->save();
                }

                $this->rememberIntendedUrl($request);

                // Check if 2FA is enabled
                if ($user->two_factor_enabled) {
                    session(['2fa_user_id' => $user->id]);

                    return redirect()->route('2fa.challenge');
                }

                Auth::login($user, $remember);
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        }

        Log::warning('Failed login attempt for: '.$email);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Seed Laravel's "intended URL" from a client-supplied return path so that a
     * user bounced to the login page after their session expires lands back on
     * the page they were working on. Only safe, same-origin relative paths are
     * accepted, and any middleware-captured intended URL always wins.
     */
    private function rememberIntendedUrl(Request $request): void
    {
        if ($request->session()->has('url.intended')) {
            return;
        }

        $intended = trim((string) $request->input('intended'));
        if ($intended === '' || ! preg_match('#^/(?!/)#', $intended)) {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '';
        foreach (['/login', '/logout', '/2fa', '/register', '/forgot-password', '/reset-password', '/install'] as $blocked) {
            if ($path === $blocked || str_starts_with($path, $blocked.'/')) {
                return;
            }
        }

        $request->session()->put('url.intended', $intended);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
