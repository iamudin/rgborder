<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\User;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    /**
     * Generate SVG QR Code string for a check-in token.
     *
     * @param string $token
     * @return string Raw SVG
     */
    public function generateQrCodeSvg(string $token): string
    {
        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'imageBase64' => false,
                'eccLevel' => QRCode::ECC_L,
                'addQuietzone' => true,
            ]);

            return (new QRCode($options))->render($token);
        } catch (\Throwable $e) {
            // Fallback inline basic SVG representation if library fails
            return '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="#0f172a"/><text x="50" y="55" font-size="12" fill="#38bdf8" text-anchor="middle">'.htmlspecialchars($token).'</text></svg>';
        }
    }

    /**
     * Get public / staff check-in URL for a token.
     */
    public function getCheckInUrl(string $token): string
    {
        return url('/check-in/' . $token);
    }

    /**
     * Perform check-in by scanning or typing the unique check_in_token.
     *
     * @param string $token
     * @param User|null $operator
     * @return BookingPlayer
     * @throws ValidationException
     */
    public function checkInByToken(string $token, ?User $operator = null): BookingPlayer
    {
        $token = trim($token);

        // 1. Extract token if scanned as a URL with query param (e.g. https://.../check-in?token=TKN-1234)
        if (str_contains($token, 'token=')) {
            parse_str(parse_url($token, PHP_URL_QUERY) ?? '', $queryParams);
            if (! empty($queryParams['token'])) {
                $token = trim($queryParams['token']);
            }
        }

        // 2. Extract token if scanned as URL path (e.g. https://.../check-in/CHK-12345 or /tiket/CHK-12345)
        if (str_contains($token, 'check-in/') || str_contains($token, 'tiket/')) {
            $path = parse_url($token, PHP_URL_PATH) ?? $token;
            $parts = explode('/', rtrim($path, '/'));
            $lastPart = end($parts);
            if (! empty($lastPart)) {
                $token = trim($lastPart);
            }
        }

        /** @var BookingPlayer|null $player */
        $player = BookingPlayer::with(['booking.gameSession', 'team', 'playerProfile'])
            ->where('check_in_token', $token)
            ->first();

        // Fallback: Check if scanned code is a main Booking Code
        if (! $player) {
            $booking = \App\Models\Booking::with(['bookingPlayers.team', 'bookingPlayers.playerProfile', 'gameSession'])
                ->where('booking_code', $token)
                ->first();

            if ($booking) {
                $player = $booking->bookingPlayers->firstWhere('check_in_status', 'pending');
                if (! $player) {
                    $first = $booking->bookingPlayers->first();
                    if ($first && $first->isCheckedIn()) {
                        throw ValidationException::withMessages([
                            'token' => "Seluruh pemain pada booking {$booking->booking_code} sudah melakukan check-in.",
                        ]);
                    }
                }
            }
        }

        if (! $player) {
            throw ValidationException::withMessages([
                'token' => 'Kode tiket QR tidak ditemukan atau tidak valid.',
            ]);
        }

        $booking = $player->booking;
        $session = $booking->gameSession;

        if ($booking->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'token' => "Tiket ini belum dapat digunakan karena status booking masih '{$booking->status}'.",
            ]);
        }

        if ($player->isCheckedIn()) {
            throw ValidationException::withMessages([
                'token' => "Pemain {$player->player_name} sudah melakukan check-in pada {$player->checked_in_at->format('H:i')}.",
            ]);
        }

        if (in_array($session->status, ['completed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'token' => "Sesi permainan ini sudah {$session->status}.",
            ]);
        }

        $player->update([
            'check_in_status' => 'checked_in',
            'checked_in_at' => Carbon::now(),
        ]);

        // Check if all players in the session are now checked in
        $totalPlayersInSession = BookingPlayer::whereHas('booking', function ($q) use ($session) {
            $q->where('game_session_id', $session->id)->where('status', 'confirmed');
        })->count();

        $checkedInCount = BookingPlayer::whereHas('booking', function ($q) use ($session) {
            $q->where('game_session_id', $session->id)->where('status', 'confirmed');
        })->where('check_in_status', 'checked_in')->count();

        if ($totalPlayersInSession > 0 && $checkedInCount >= $totalPlayersInSession && $session->status === 'open') {
            $session->update(['status' => 'ready']);
        }

        ActivityLog::record('player.checked_in', $player, [
            'player_name' => $player->player_name,
            'team' => $player->team?->name ?? 'Belum ditentukan',
            'session_code' => $session->session_code,
            'token' => $token,
        ], $operator);

        return $player;
    }
}
