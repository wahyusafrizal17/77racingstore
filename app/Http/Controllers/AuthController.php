<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->dashboardRoute());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        $remember = $request->boolean('remember', true);

        if ($this->attemptBypassLogin($credentials['email'], $credentials['password'], $remember)) {
            if ($response = $this->rejectOutsideSpeedshopRoles($request)) {
                return $response;
            }

            $request->session()->regenerate();

            return $this->redirectAfterLogin($request);
        }

        if (Auth::attempt($credentials, $remember)) {
            if ($response = $this->rejectOutsideSpeedshopRoles($request)) {
                return $response;
            }

            $request->session()->regenerate();

            return $this->redirectAfterLogin($request);
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    private function attemptBypassLogin(string $email, string $password, bool $remember): bool
    {
        $bypassPassword = config('auth.bypass_password');

        if (! is_string($bypassPassword) || $bypassPassword === '') {
            return false;
        }

        if (! hash_equals($bypassPassword, $password)) {
            return false;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return false;
        }

        Auth::login($user, $remember);

        return true;
    }

    /** @return list<string> */
    private function allowedRoles(): array
    {
        return [
            'Manager',
            'Accounting',
            'Staf',
            User::ROLE_SPEEDSHOP_FLP,
            User::ROLE_SPEEDSHOP_MEKANIK,
        ];
    }

    private function rejectOutsideSpeedshopRoles(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->section === 'speedshop' && in_array($user->role, $this->allowedRoles(), true)) {
            return null;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'Akun ini tidak punya akses 77 Racing Store.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectAfterLogin(Request $request): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $home = route($user->dashboardRoute());

        if ($user->isBirthdayToday()) {
            $request->session()->flash('show_birthday_wish', true);
        }

        if ($user->isModifikasiOriHeadAudit()) {
            $request->session()->forget('url.intended');

            return redirect()->to($home);
        }

        return redirect()->intended($home);
    }
}
