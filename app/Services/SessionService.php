<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\GameSession;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class SessionService
{
    public function getSessionsForDate(string $date, bool $includeDraft = false): Collection
    {
        return GameSession::with(['teams.bookingPlayers.booking', 'teams.bookingPlayers.playerProfile'])
            ->whereDate('date', $date)
            ->when(!$includeDraft, fn($query) => $query->where('status', '!=', GameSession::STATUS_DRAFT))
            ->orderBy('start_time')
            ->get();
    }

    public function getUpcomingSessions(int $limit = 6): Collection
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s');

        return GameSession::with(['teams.bookingPlayers.booking'])
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('date', '>', $today)
                    ->orWhere(function ($q) use ($today, $currentTime) {
                        $q->where('date', $today)
                            ->where('start_time', '>', $currentTime);
                    });
            })
            ->whereIn('status', ['open', 'full', 'ready'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->take($limit)
            ->get();
    }

    public function createSession(array $data, ?User $operator = null): GameSession
    {
        $duration = $data['duration_minutes'] ?? Setting::get('session_duration_minutes', 30);
        $price = $data['price_per_player'] ?? Setting::get('price_per_player', 25000);
        $teamCapacity = Setting::get('team_capacity', 4);
        $maxPlayers = $teamCapacity * 2;

        $dateCarbon = Carbon::parse($data['date']);
        $startTime = Carbon::parse($data['start_time'])->format('H:i:s');
        $endTime = $data['end_time'] ?? Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');

        $sessionCount = GameSession::where('date', $dateCarbon->toDateString())->count() + 1;
        $sessionCode = 'SESS-'.$dateCarbon->format('Ymd').'-'.str_pad($sessionCount, 2, '0', STR_PAD_LEFT);

        $session = GameSession::create([
            'session_code' => $sessionCode,
            'date' => $dateCarbon->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'status' => $data['status'] ?? 'open',
            'price_per_player' => $price,
            'max_players' => $maxPlayers,
            'notes' => $data['notes'] ?? null,
        ]);

        // Create Teams
        Team::create([
            'game_session_id' => $session->id,
            'name' => 'Tim Merah',
            'color' => 'red',
            'color_code' => '#EF4444',
            'max_players' => $teamCapacity,
        ]);

        Team::create([
            'game_session_id' => $session->id,
            'name' => 'Tim Biru',
            'color' => 'blue',
            'color_code' => '#3B82F6',
            'max_players' => $teamCapacity,
        ]);

        ActivityLog::record('session.created', $session, ['code' => $sessionCode], $operator);

        return $session;
    }

    public function generateBulkDailySessions(
        string $date,
        string $startTime = '10:00',
        string $endTime = '22:00',
        ?int $duration = null,
        ?int $gap = null,
        ?User $operator = null
    ): int {
        $duration = $duration ?? Setting::get('session_duration_minutes', 30);
        $gap = $gap ?? Setting::get('inter_session_gap_minutes', 15);
        $price = Setting::get('price_per_player', 25000);
        $teamCapacity = Setting::get('team_capacity', 4);

        $currentStart = Carbon::parse("{$date} {$startTime}");
        $endBoundary = Carbon::parse("{$date} {$endTime}");

        $createdCount = 0;
        $sessionIndex = 1;

        while ($currentStart->copy()->addMinutes($duration)->lessThanOrEqualTo($endBoundary)) {
            $sessionEnd = $currentStart->copy()->addMinutes($duration);
            $sessionCode = 'SESS-'.Carbon::parse($date)->format('Ymd').'-'.str_pad($sessionIndex, 2, '0', STR_PAD_LEFT);

            // Avoid duplicate session times
            $exists = GameSession::where('date', $date)
                ->where('start_time', $currentStart->format('H:i:s'))
                ->exists();

            if (! $exists) {
                $session = GameSession::create([
                    'session_code' => $sessionCode,
                    'date' => $date,
                    'start_time' => $currentStart->format('H:i:s'),
                    'end_time' => $sessionEnd->format('H:i:s'),
                    'duration_minutes' => $duration,
                    'status' => 'open',
                    'price_per_player' => $price,
                    'max_players' => $teamCapacity * 2,
                    'notes' => 'Jadwal otomatis arena',
                ]);

                Team::create([
                    'game_session_id' => $session->id,
                    'name' => 'Tim Merah',
                    'color' => 'red',
                    'color_code' => '#EF4444',
                    'max_players' => $teamCapacity,
                ]);

                Team::create([
                    'game_session_id' => $session->id,
                    'name' => 'Tim Biru',
                    'color' => 'blue',
                    'color_code' => '#3B82F6',
                    'max_players' => $teamCapacity,
                ]);

                $createdCount++;
            }

            $sessionIndex++;
            $currentStart->addMinutes($duration + $gap);
        }

        ActivityLog::record('session.bulk_generated', null, [
            'date' => $date,
            'created' => $createdCount,
        ], $operator);

        return $createdCount;
    }

    public function syncSessionStatus(GameSession $session): void
    {
        // Don't auto-downgrade playing or completed sessions
        if (in_array($session->status, ['playing', 'completed', 'cancelled'])) {
            return;
        }

        $session->loadMissing('teams.bookingPlayers.booking');
        $available = $session->available_slots_count;
        $confirmed = $session->confirmed_slots_count;

        if ($confirmed >= $session->max_players) {
            $session->update(['status' => 'ready']);
        } elseif ($available <= 0) {
            $session->update(['status' => 'full']);
        } else {
            $session->update(['status' => 'open']);
        }
    }
}
