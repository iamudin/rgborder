<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\GameSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class GameSessionService
{
    /**
     * Start the 30-minute game session.
     *
     * @param GameSession $session
     * @param User|null $operator
     * @return GameSession
     * @throws ValidationException
     */
    public function startGame(GameSession $session, ?User $operator = null): GameSession
    {
        if ($session->isPlaying()) {
            return $session; // Already playing
        }

        if ($session->isCompleted() || $session->isCancelled()) {
            throw ValidationException::withMessages([
                'session' => "Sesi ini sudah {$session->status} dan tidak dapat dimulai kembali.",
            ]);
        }

        $session->loadMissing('teams.bookingPlayers.booking');

        if ($session->confirmed_slots_count < 1) {
            throw ValidationException::withMessages([
                'session' => 'Sesi tidak dapat dimulai karena belum ada pemain yang terkonfirmasi.',
            ]);
        }

        $now = Carbon::now();
        $session->update([
            'status' => 'playing',
            'started_at' => $now,
            'ended_at' => null,
        ]);

        ActivityLog::record('session.started', $session, [
            'session_code' => $session->session_code,
            'started_at' => $now->toDateTimeString(),
            'players' => $session->confirmed_slots_count,
        ], $operator);

        return $session;
    }

    /**
     * Complete the game session.
     *
     * @param GameSession $session
     * @param string|null $reason
     * @param User|null $operator
     * @return GameSession
     */
    public function finishGame(GameSession $session, ?string $reason = null, ?User $operator = null): GameSession
    {
        $now = Carbon::now();

        $session->update([
            'status' => 'completed',
            'ended_at' => $now,
            'notes' => trim(($session->notes ?? '').($reason ? "\nSelesai: {$reason}" : '')),
        ]);

        ActivityLog::record('session.completed', $session, [
            'session_code' => $session->session_code,
            'ended_at' => $now->toDateTimeString(),
            'reason' => $reason ?? 'Waktu permainan reguler selesai',
        ], $operator);

        return $session;
    }

    /**
     * Cancel the game session with reason.
     *
     * @param GameSession $session
     * @param string $reason
     * @param User|null $operator
     * @return GameSession
     */
    public function cancelGame(GameSession $session, string $reason, ?User $operator = null): GameSession
    {
        $session->update([
            'status' => 'cancelled',
            'notes' => trim(($session->notes ?? '')."\nDibatalkan oleh admin: {$reason}"),
        ]);

        // Cancel pending/confirmed bookings if any
        foreach ($session->bookings as $booking) {
            if (! in_array($booking->status, ['cancelled', 'expired'])) {
                $booking->update([
                    'status' => 'cancelled',
                    'notes' => 'Sesi arena dibatalkan: '.$reason,
                ]);
            }
        }

        ActivityLog::record('session.cancelled', $session, [
            'session_code' => $session->session_code,
            'reason' => $reason,
        ], $operator);

        return $session;
    }

    /**
     * Automatically scan and complete playing sessions whose duration has elapsed on the server.
     *
     * @return int Count of auto-completed sessions
     */
    public function checkAndAutoCompletePlayingSessions(): int
    {
        $playingSessions = GameSession::where('status', 'playing')
            ->whereNotNull('started_at')
            ->get();

        $count = 0;
        foreach ($playingSessions as $session) {
            $durationMinutes = $session->duration_minutes;
            $serverEndTime = $session->started_at->copy()->addMinutes($durationMinutes);

            if (Carbon::now()->greaterThanOrEqualTo($serverEndTime)) {
                $this->finishGame($session, 'Waktu 30 menit otomatis berakhir di server');
                $count++;
            }
        }

        return $count;
    }
}
