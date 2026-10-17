<?php

namespace App\Filament\Pages;

use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Services\CheckInService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CheckInScannerPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Arena';

    protected static ?string $navigationLabel = 'Scan QR & Check-In';

    protected static ?string $title = 'Stasiun Scan QR & Check-In';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.check-in-scanner-page';

    public string $manualCode = '';

    public ?int $selectedSessionId = null;

    public ?array $lastResult = null;

    public static function getNavigationBadge(): ?string
    {
        $today = Carbon::today()->toDateString();
        $pendingCount = BookingPlayer::whereHas('booking', function ($q) use ($today) {
            $q->whereHas('gameSession', function ($s) use ($today) {
                $s->whereDate('date', $today);
            })->where('status', 'confirmed');
        })->where('check_in_status', 'pending')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // Auto-select active or nearest upcoming session
        $activeSession = GameSession::whereDate('date', $today)
            ->where('status', 'playing')
            ->first();

        if (! $activeSession) {
            $activeSession = GameSession::whereDate('date', $today)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime)
                ->whereIn('status', ['ready', 'open', 'full'])
                ->first();
        }

        if (! $activeSession) {
            $activeSession = GameSession::whereDate('date', $today)
                ->where('start_time', '>', $currentTime)
                ->orderBy('start_time')
                ->first();
        }

        $this->selectedSessionId = $activeSession?->id;
    }

    public function processCheckIn(?string $code = null, ?CheckInService $service = null): void
    {
        $token = trim($code ?: $this->manualCode);
        if (empty($token)) {
            Notification::make()
                ->title('Kode Kosong')
                ->body('Silakan scan QR code atau masukkan kode tiket/booking terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        $service = $service ?: app(CheckInService::class);

        try {
            $player = $service->checkInByToken($token, Auth::user());
            $team = $player->team;
            $isRed = $team && str_contains(strtolower($team->name), 'merah');

            $this->lastResult = [
                'status' => 'success',
                'player_id' => $player->id,
                'player_name' => $player->player_name,
                'callsign' => $player->playerProfile?->callsign ?? $player->player_name,
                'team_name' => $team?->name ?? 'Tim Menunggu',
                'team_color' => $isRed ? 'red' : 'blue',
                'session_name' => $player->booking->gameSession->session_name,
                'session_code' => $player->booking->gameSession->session_code,
                'booking_code' => $player->booking->booking_code,
                'time' => Carbon::now()->format('H:i:s WIB'),
            ];

            $this->manualCode = '';

            Notification::make()
                ->title('Check-In Berhasil!')
                ->body("{$player->player_name} ({$this->lastResult['callsign']}) siap di {$this->lastResult['team_name']}.")
                ->success()
                ->send();

            $this->dispatch('check-in-processed', [
                'status' => 'success',
                'result' => $this->lastResult,
            ]);

        } catch (ValidationException $e) {
            $errors = $e->errors();
            $msg = reset($errors)[0] ?? $e->getMessage();

            $this->lastResult = [
                'status' => 'error',
                'message' => $msg,
                'time' => Carbon::now()->format('H:i:s WIB'),
            ];

            Notification::make()
                ->title('Check-In Gagal')
                ->body($msg)
                ->danger()
                ->send();

            $this->dispatch('check-in-processed', [
                'status' => 'error',
                'message' => $msg,
            ]);

        } catch (\Throwable $e) {
            $this->lastResult = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'time' => Carbon::now()->format('H:i:s WIB'),
            ];

            Notification::make()
                ->title('Gagal Memproses Check-In')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->dispatch('check-in-processed', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function checkInPlayerDirectly(int $bookingPlayerId, CheckInService $service): void
    {
        $player = BookingPlayer::find($bookingPlayerId);
        if (! $player) {
            return;
        }

        $this->processCheckIn($player->check_in_token, $service);
    }

    protected function getViewData(): array
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        // Today's sessions for quick tabs / selector
        $sessionsToday = GameSession::whereDate('date', $today)
            ->orderBy('start_time')
            ->get();

        // Selected or all players today
        $query = BookingPlayer::with(['booking.gameSession', 'team', 'playerProfile'])
            ->whereHas('booking', function ($q) use ($today) {
                $q->whereHas('gameSession', function ($s) use ($today) {
                    $s->whereDate('date', $today);
                })->where('status', 'confirmed');
            });

        if ($this->selectedSessionId) {
            $query->whereHas('booking', fn ($q) => $q->where('game_session_id', $this->selectedSessionId));
        }

        $allPlayers = $query->get();
        $checkedInPlayers = $allPlayers->where('check_in_status', 'checked_in')->sortByDesc('checked_in_at');
        $pendingPlayers = $allPlayers->where('check_in_status', 'pending');

        $totalConfirmedToday = BookingPlayer::whereHas('booking', function ($q) use ($today) {
            $q->whereHas('gameSession', function ($s) use ($today) {
                $s->whereDate('date', $today);
            })->where('status', 'confirmed');
        })->count();

        $totalCheckedInToday = BookingPlayer::whereHas('booking', function ($q) use ($today) {
            $q->whereHas('gameSession', function ($s) use ($today) {
                $s->whereDate('date', $today);
            })->where('status', 'confirmed');
        })->where('check_in_status', 'checked_in')->count();

        return [
            'currentTime' => $now->format('H:i:s'),
            'currentDate' => $now->locale('id')->isoFormat('dddd, D MMMM Y'),
            'sessionsToday' => $sessionsToday,
            'checkedInPlayers' => $checkedInPlayers,
            'pendingPlayers' => $pendingPlayers,
            'totalConfirmedToday' => $totalConfirmedToday,
            'totalCheckedInToday' => $totalCheckedInToday,
            'pendingCountToday' => max(0, $totalConfirmedToday - $totalCheckedInToday),
            'selectedSession' => $this->selectedSessionId ? GameSession::with('teams')->find($this->selectedSessionId) : null,
        ];
    }
}
