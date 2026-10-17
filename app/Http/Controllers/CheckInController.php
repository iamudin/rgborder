<?php

namespace App\Http\Controllers;

use App\Models\BookingPlayer;
use App\Services\CheckInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckInController extends Controller
{
    /**
     * Display digital ticket / check-in screen for a scanned token.
     */
    public function show(string $token, CheckInService $checkInService): View
    {
        $player = BookingPlayer::with([
            'booking.gameSession.teams',
            'team',
            'playerProfile',
            'booking.user',
        ])
            ->where('check_in_token', $token)
            ->first();

        // Also fallback to search by booking code
        if (! $player) {
            $booking = \App\Models\Booking::with(['bookingPlayers.team', 'bookingPlayers.playerProfile', 'gameSession.teams'])
                ->where('booking_code', $token)
                ->first();

            if ($booking) {
                $player = $booking->bookingPlayers->firstWhere('check_in_status', 'pending')
                    ?? $booking->bookingPlayers->first();
            }
        }

        $qrSvg = null;
        if ($player) {
            $checkInUrl = $checkInService->getCheckInUrl($player->check_in_token);
            $qrSvg = $checkInService->generateQrCodeSvg($checkInUrl);
        }

        return view('checkin.ticket', [
            'token' => $token,
            'player' => $player,
            'qrSvg' => $qrSvg,
            'isAdmin' => Auth::check() && Auth::user()->isAdmin(),
        ]);
    }

    /**
     * Process staff check-in confirmation from the ticket page.
     */
    public function confirm(Request $request, string $token, CheckInService $checkInService): RedirectResponse
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Silakan login sebagai staf/admin arena untuk melakukan check-in pemain.');
        }

        try {
            $player = $checkInService->checkInByToken($token, Auth::user());

            return redirect()
                ->route('check-in.show', $player->check_in_token)
                ->with('success', "Check-In Berhasil! Pemain {$player->player_name} telah dikonfirmasi hadir untuk " . ($player->team?->name ?? 'Arena') . '.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $msg = reset($errors)[0] ?? $e->getMessage();

            return redirect()
                ->route('check-in.show', $token)
                ->with('error', $msg);
        } catch (\Throwable $e) {
            return redirect()
                ->route('check-in.show', $token)
                ->with('error', $e->getMessage());
        }
    }
}
