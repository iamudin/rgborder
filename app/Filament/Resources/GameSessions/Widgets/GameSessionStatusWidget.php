<?php

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Filament\Resources\GameSessions\Pages\SessionDetail;
use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use App\Services\CheckInService;
use App\Services\GameSessionService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class GameSessionStatusWidget extends Widget
{
    protected string $view = 'filament.resources.game-sessions.widgets.game-session-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    public bool $showQrModal = false;

    public string $manualToken = '';

    public ?array $lastCheckIn = null;

    public function openQrModal(): void
    {
        $this->showQrModal = true;
        $this->manualToken = '';
        $this->lastCheckIn = null;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->manualToken = '';
    }

    public function processCheckIn(?string $token = null, ?CheckInService $checkInService = null): void
    {
        $checkInService = $checkInService ?? app(CheckInService::class);
        $scannedToken = trim($token ?? $this->manualToken);

        if (empty($scannedToken)) {
            Notification::make()
                ->title('Kode QR Kosong')
                ->body('Pindai QR code tiket atau masukkan kode token secara manual.')
                ->warning()
                ->send();

            return;
        }

        try {
            $player = $checkInService->checkInByToken($scannedToken, Auth::user());
            $booking = $player->booking;
            $session = $booking->gameSession;
            $team = $player->team;

            $this->lastCheckIn = [
                'status' => 'success',
                'player_name' => $player->player_name,
                'team_name' => $team?->name ?? 'Belum Dibagi',
                'team_color' => $team?->color ?? 'slate',
                'booking_code' => $booking->booking_code,
                'session_code' => $session?->session_code ?? '-',
                'session_time' => $session?->formatted_time_range ?? '-',
                'checked_in_at' => Carbon::now()->format('H:i:s WIB'),
            ];

            $this->manualToken = '';

            Notification::make()
                ->title('Check-In Berhasil!')
                ->body("{$player->player_name} siap bertempur di " . ($team?->name ?? 'Arena') . '.')
                ->success()
                ->send();

            $this->dispatch('check-in-success', data: $this->lastCheckIn);
        } catch (\Throwable $e) {
            $this->lastCheckIn = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'time' => Carbon::now()->format('H:i:s WIB'),
            ];

            Notification::make()
                ->title('Check-In Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startGameSession(int $sessionId, GameSessionService $service): void
    {
        $session = GameSession::find($sessionId);
        if (! $session) {
            return;
        }

        try {
            $service->startGame($session, Auth::user());
            Notification::make()
                ->title('Pertempuran Dimulai!')
                ->body("Sesi {$session->session_code} aktif berjalan.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memulai Sesi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function finishGameSession(int $sessionId, GameSessionService $service): void
    {
        $session = GameSession::find($sessionId);
        if (! $session) {
            return;
        }

        try {
            $service->finishGame($session, 'Selesai dari widget operasional', Auth::user());
            Notification::make()
                ->title('Sesi Telah Selesai')
                ->body("Sesi {$session->session_code} telah ditandai selesai.")
                ->info()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menyelesaikan Sesi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // 1. Sesi yang sedang berjalan di jam saat ini
        $activeSession = GameSession::with(['teams.bookingPlayers.booking', 'teams.bookingPlayers.playerProfile'])
            ->where('status', 'playing')
            ->first();

        if (! $activeSession) {
            // Check if there is an open or ready session whose time frame matches current time
            $activeSession = GameSession::with(['teams.bookingPlayers.booking', 'teams.bookingPlayers.playerProfile'])
                ->whereDate('date', $today)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime)
                ->whereIn('status', ['ready', 'open', 'full'])
                ->first();
        }

        // 2. Sesi paling dekat berikutnya
        $nextSession = GameSession::with(['teams.bookingPlayers.booking', 'teams.bookingPlayers.playerProfile'])
            ->whereDate('date', $today)
            ->where('start_time', '>', $currentTime)
            ->whereIn('status', ['open', 'full', 'ready'])
            ->when($activeSession, fn ($q) => $q->where('id', '!=', $activeSession->id))
            ->orderBy('start_time')
            ->first();

        // If no more upcoming sessions today, get tomorrow's earliest session
        if (! $nextSession) {
            $nextSession = GameSession::with(['teams.bookingPlayers.booking', 'teams.bookingPlayers.playerProfile'])
                ->whereDate('date', '>', $today)
                ->whereIn('status', ['open', 'full', 'ready'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->first();
        }

        // 3. Ringkasan Statistik Operasional Hari Ini
        $totalSessionsToday = GameSession::whereDate('date', $today)->count();
        $playingCount = GameSession::where('status', 'playing')->count();
        $completedToday = GameSession::whereDate('date', $today)->where('status', 'completed')->count();

        $maxCapacityToday = (int) GameSession::whereDate('date', $today)->sum('max_players');
        $confirmedPlayersToday = BookingPlayer::whereHas('booking', function ($q) use ($today) {
            $q->whereHas('gameSession', function ($s) use ($today) {
                $s->whereDate('date', $today);
            })->where('status', 'confirmed');
        })->count();

        $occupancyPercent = $maxCapacityToday > 0 ? round(($confirmedPlayersToday / $maxCapacityToday) * 100, 1) : 0;

        $revenueToday = (int) Payment::where('status', 'paid')
            ->whereDate('verified_at', $today)
            ->sum('amount');

        // Check-in progress for active and next session
        $activeCheckedInCount = 0;
        $activeTotalPlayers = 0;
        if ($activeSession) {
            $activeTotalPlayers = $activeSession->occupied_slots_count;
            $activeCheckedInCount = $activeSession->teams->flatMap->bookingPlayers->where('check_in_status', 'checked_in')->count();
        }

        $nextCheckedInCount = 0;
        $nextTotalPlayers = 0;
        $nextCountdownText = '';
        if ($nextSession) {
            $nextTotalPlayers = $nextSession->occupied_slots_count;
            $nextCheckedInCount = $nextSession->teams->flatMap->bookingPlayers->where('check_in_status', 'checked_in')->count();

            $nextStart = Carbon::parse($nextSession->date->format('Y-m-d') . ' ' . $nextSession->start_time);
            if ($nextStart->isFuture()) {
                $diffMinutes = $now->diffInMinutes($nextStart);
                if ($diffMinutes > 60) {
                    $nextCountdownText = floor($diffMinutes / 60) . ' jam ' . ($diffMinutes % 60) . ' menit lagi';
                } else {
                    $nextCountdownText = max(1, $diffMinutes) . ' menit lagi';
                }
            } else {
                $nextCountdownText = 'Waktu mulai telah tiba';
            }
        }

        return [
            'currentTime' => $now->format('H:i:s'),
            'currentDateFormatted' => $now->locale('id')->isoFormat('dddd, D MMMM Y'),
            'activeSession' => $activeSession,
            'activeCheckedInCount' => $activeCheckedInCount,
            'activeTotalPlayers' => $activeTotalPlayers,
            'nextSession' => $nextSession,
            'nextCheckedInCount' => $nextCheckedInCount,
            'nextTotalPlayers' => $nextTotalPlayers,
            'nextCountdownText' => $nextCountdownText,
            'totalSessionsToday' => $totalSessionsToday,
            'playingCount' => $playingCount,
            'completedToday' => $completedToday,
            'confirmedPlayersToday' => $confirmedPlayersToday,
            'maxCapacityToday' => $maxCapacityToday,
            'occupancyPercent' => $occupancyPercent,
            'revenueToday' => $revenueToday,
        ];
    }
}
