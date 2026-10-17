<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        if ($request->has('redirect_to')) {
            session(['url.intended' => $request->get('redirect_to')]);
        }

        return view('auth.login');
    }

    public function redirectToGoogle(): RedirectResponse
    {
        // If Google credentials are not set up, provide a graceful message or mock in local
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->with('warning', 'Google OAuth Client ID belum dikonfigurasi di file .env. Silakan gunakan tombol Masuk Cepat / Demo untuk pengujian lokal.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Gagal melakukan autentikasi Google: '.$e->getMessage());
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?? 'Player-'.Str::random(4),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]);

            // Auto create PlayerProfile
            PlayerProfile::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
                'is_guest' => false,
            ]);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        ActivityLog::record('auth.google_login', $user, ['email' => $user->email], $user);

        // If user doesn't have phone number yet, suggest completing it
        if (empty($user->phone)) {
            return redirect()->intended(route('profile'))->with('info', 'Selamat datang! Silakan lengkapi nomor WhatsApp Anda untuk konfirmasi booking dan tiket.');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * One-click development login for seamless local testing without external Google API setup.
     */
    public function devLogin(Request $request): RedirectResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(403, 'Aksi ini hanya tersedia di lingkungan development.');
        }

        $email = $request->input('email', 'player@rgbarena.com');
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $request->input('name', 'CyberStriker'),
                'phone' => '089876543210',
                'role' => 'customer',
                'email_verified_at' => now(),
            ]
        );

        PlayerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => $user->phone ?? '089876543210',
                'email' => $user->email,
                'is_guest' => false,
            ]
        );

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        ActivityLog::record('auth.dev_login', $user, ['email' => $user->email], $user);

        return redirect()->intended(route('dashboard'))->with('success', "Berhasil masuk sebagai {$user->name} ({$user->email})");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah berhasil keluar.');
    }
}
