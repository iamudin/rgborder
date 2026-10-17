<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TeamAssignmentService
{
    /**
     * Determine best team for a booking and ensure group members stay together.
     *
     * @param GameSession $session
     * @param int $playerCount
     * @param int|null $preferredTeamId
     * @return Team
     * @throws ValidationException
     */
    public function resolveTeamForBooking(GameSession $session, int $playerCount, ?int $preferredTeamId = null): Team
    {
        $session->loadMissing('teams.bookingPlayers.booking');
        $teams = $session->teams;

        if ($teams->isEmpty()) {
            throw ValidationException::withMessages([
                'team' => 'Sesi permainan ini belum memiliki tim yang dikonfigurasi.',
            ]);
        }

        // If specific team requested
        if ($preferredTeamId) {
            $targetTeam = $teams->firstWhere('id', $preferredTeamId);

            if (! $targetTeam) {
                throw ValidationException::withMessages([
                    'team' => 'Tim yang dipilih tidak valid untuk sesi ini.',
                ]);
            }

            if ($targetTeam->available_slots_count < $playerCount) {
                throw ValidationException::withMessages([
                    'team' => "Kapasitas {$targetTeam->name} tidak mencukupi untuk {$playerCount} pemain (tersisa {$targetTeam->available_slots_count} slot).",
                ]);
            }

            return $targetTeam;
        }

        // Auto assignment:
        // Rule: Groups MUST be together in the SAME team!
        // Find teams with at least $playerCount available slots
        $eligibleTeams = $teams->filter(function (Team $team) use ($playerCount) {
            return $team->available_slots_count >= $playerCount;
        });

        if ($eligibleTeams->isEmpty()) {
            throw ValidationException::withMessages([
                'team' => "Tidak ada tim yang memiliki {$playerCount} slot kosong sekaligus. Anggota kelompok tidak dapat dipisahkan.",
            ]);
        }

        // Among eligible teams, choose the one with fewest occupied players (balanced)
        // If tied, prefer lowest id (deterministic, typically Red team first)
        return $eligibleTeams->sortBy(function (Team $team) {
            return $team->occupied_slots_count * 1000 + $team->id;
        })->first();
    }

    /**
     * Move an individual player to another team with validation.
     *
     * @param BookingPlayer $player
     * @param Team $newTeam
     * @param User|null $operator
     * @return bool
     * @throws ValidationException
     */
    public function movePlayer(BookingPlayer $player, Team $newTeam, ?User $operator = null): bool
    {
        $session = $player->booking->gameSession;

        if (in_array($session->status, ['playing', 'completed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'team' => 'Tidak dapat memindahkan pemain saat sesi sedang berlangsung atau telah selesai.',
            ]);
        }

        if ($newTeam->game_session_id !== $session->id) {
            throw ValidationException::withMessages([
                'team' => 'Tim tujuan tidak berada dalam sesi permainan yang sama.',
            ]);
        }

        if ($player->team_id === $newTeam->id) {
            return true; // Already on this team
        }

        if ($newTeam->available_slots_count <= 0) {
            throw ValidationException::withMessages([
                'team' => "Tim tujuan ({$newTeam->name}) sudah penuh (kapasitas maksimal {$newTeam->max_players} pemain).",
            ]);
        }

        $oldTeamName = $player->team?->name ?? 'Belum ada tim';
        $player->update(['team_id' => $newTeam->id]);

        ActivityLog::record('team.player_moved', $player, [
            'player_name' => $player->player_name,
            'from_team' => $oldTeamName,
            'to_team' => $newTeam->name,
            'session_code' => $session->session_code,
        ], $operator);

        return true;
    }
}
