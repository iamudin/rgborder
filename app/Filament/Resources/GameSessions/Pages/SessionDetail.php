<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use App\Models\Team;
use App\Services\BookingService;
use App\Services\CheckInService;
use App\Services\GameSessionService;
use App\Services\PaymentService;
use App\Services\TeamAssignmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class SessionDetail extends ViewRecord
{
    protected static string $resource = GameSessionResource::class;

    protected string $view = 'filament.resources.game-sessions.pages.session-detail';

    public ?string $walkInName = '';
    public ?string $walkInPhone = '';
    public ?int $walkInTeamId = null;
    public string $walkInPaymentMethod = 'cash_on_site';
    public bool $walkInIsPaid = true;

    public ?string $manualCheckInToken = '';

    public function getTitle(): string
    {
        return 'Pusat Operasional: '.$this->record->session_code;
    }

    public function addWalkInPlayer(BookingService $bookingService): void
    {
        $this->validate([
            'walkInName' => ['required', 'string', 'min:2', 'max:50'],
            'walkInPhone' => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'],
            'walkInTeamId' => ['nullable', 'exists:teams,id'],
        ], [
            'walkInName.required' => 'Nama pemain walk-in wajib diisi.',
            'walkInPhone.regex' => 'Format nomor WhatsApp tidak valid.',
        ]);

        try {
            $booking = $bookingService->createWalkInBooking(
                $this->record,
                [['name' => $this->walkInName, 'phone' => $this->walkInPhone]],
                $this->walkInTeamId,
                $this->walkInPaymentMethod,
                $this->walkInIsPaid,
                Auth::user()
            );

            $this->reset(['walkInName', 'walkInPhone', 'walkInTeamId']);
            $this->record->refresh();

            Notification::make()
                ->title('Pemain Walk-In Berhasil Ditambahkan')
                ->body("Pemain berhasil didaftarkan dengan kode booking {$booking->booking_code}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menambah Pemain Walk-In')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function movePlayerToTeam(int $bookingPlayerId, int $targetTeamId, TeamAssignmentService $teamService): void
    {
        $player = BookingPlayer::findOrFail($bookingPlayerId);
        $targetTeam = Team::findOrFail($targetTeamId);

        try {
            $teamService->movePlayer($player, $targetTeam, Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Pemain Berhasil Dipindahkan')
                ->body("{$player->player_name} dipindahkan ke {$targetTeam->name}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memindahkan Pemain')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkInPlayer(int $bookingPlayerId): void
    {
        $player = BookingPlayer::findOrFail($bookingPlayerId);

        if ($player->isCheckedIn()) {
            Notification::make()->title('Pemain sudah check-in sebelumnya')->warning()->send();
            return;
        }

        $player->checkIn();
        $this->record->refresh();

        Notification::make()
            ->title('Check-in Berhasil')
            ->body("{$player->player_name} siap bertempur di arena.")
            ->success()
            ->send();
    }

    public function checkInByToken(CheckInService $checkInService): void
    {
        $token = trim($this->manualCheckInToken ?? '');
        if (empty($token)) {
            Notification::make()->title('Masukkan kode tiket atau token QR')->warning()->send();
            return;
        }

        try {
            $player = $checkInService->checkInByToken($token, Auth::user());
            $this->reset('manualCheckInToken');
            $this->record->refresh();

            Notification::make()
                ->title('Check-In Tiket QR Berhasil')
                ->body("Pemain: {$player->player_name} | Tim: {$player->team?->name}")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Check-In')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function verifyPlayerPayment(int $paymentId, PaymentService $paymentService): void
    {
        $payment = Payment::findOrFail($paymentId);

        try {
            $paymentService->verifyPayment($payment, Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Pembayaran Berhasil Diverifikasi')
                ->body("Booking {$payment->booking->booking_code} kini berstatus Terkonfirmasi.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Verifikasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function removePlayer(int $bookingPlayerId, BookingService $bookingService): void
    {
        $player = BookingPlayer::findOrFail($bookingPlayerId);
        $booking = $player->booking;

        try {
            $bookingService->cancelBooking($booking, 'Dikeluarkan oleh Operator Arena', Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Pemain Berhasil Dikeluarkan')
                ->body('Booking dibatalkan dan slot telah kembali tersedia.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Mengeluarkan Pemain')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startGame(GameSessionService $sessionService): void
    {
        try {
            $sessionService->startGame($this->record, Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Pertempuran Dimulai!')
                ->body('Timer 30 menit server telah aktif. Lampu arena menyala!')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memulai Permainan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function finishGame(GameSessionService $sessionService): void
    {
        try {
            $sessionService->finishGame($this->record, 'Selesai oleh Admin di Ruang Kontrol', Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Sesi Permainan Selesai')
                ->body('Hasil dan durasi telah dicatat ke dalam sistem.')
                ->info()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menyelesaikan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelSession(GameSessionService $sessionService): void
    {
        try {
            $sessionService->cancelGame($this->record, 'Dibatalkan oleh Admin', Auth::user());
            $this->record->refresh();

            Notification::make()
                ->title('Sesi Permainan Dibatalkan')
                ->body('Seluruh slot dibebaskan.')
                ->warning()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Membatalkan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
