<?php

namespace App\Http\Controllers;

use App\Models\PlayerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        $profile = PlayerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => $user->phone ?? '',
                'email' => $user->email,
                'is_guest' => false,
            ]
        );

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'],
        ], [
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        PlayerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $user->email,
            ]
        );

        if ($request->filled('redirect_to')) {
            $redirectTo = $request->input('redirect_to');
            if (str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, url('/'))) {
                return redirect($redirectTo)->with('success', 'Profil dan nomor WhatsApp berhasil diperbarui! Form order kini aktif.');
            }
        }

        return redirect()->route('profile')->with('success', 'Profil dan nomor WhatsApp Anda berhasil diperbarui!');
    }
}
