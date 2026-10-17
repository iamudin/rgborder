<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\GameSessions\Widgets\GameSessionStatusWidget;
use App\Models\GameSession;
use App\Services\CheckInService;
use App\Services\SessionService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGameSessions extends ListRecords
{
    protected static string $resource = GameSessionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GameSessionStatusWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check_in_qr')
                ->label('Check-In Tiket QR')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->modalHeading('Check-In Cepat Tiket QR')
                ->modalDescription('Pindai QR code atau ketik kode tiket / booking untuk validasi kehadiran pemain.')
                ->form([
                    TextInput::make('token')
                        ->label('Kode Tiket QR / Token / Kode Booking')
                        ->placeholder('Contoh: TKN-ABCD-1234 atau BOOK-20261016-01')
                        ->required()
                        ->autofocus(),
                ])
                ->action(function (array $data, CheckInService $checkInService) {
                    try {
                        $player = $checkInService->checkInByToken($data['token'], auth()->user());
                        $team = $player->team;

                        Notification::make()
                            ->title('Check-In Berhasil!')
                            ->body("Pemain {$player->player_name} terdaftar di " . ($team?->name ?? 'Arena') . '.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Check-In Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('bulk_generate')
                ->label('Generate Jadwal Massal')
                ->icon('heroicon-o-bolt')
                ->color('cyan')
                ->form([
                    DatePicker::make('date')
                        ->label('Tanggal Jadwal')
                        ->required()
                        ->default(now()->addDay()),
                    TimePicker::make('start_time')
                        ->label('Jam Buka Pertama')
                        ->default('10:00')
                        ->required(),
                    TimePicker::make('end_time')
                        ->label('Jam Tutup Terakhir')
                        ->default('22:00')
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->label('Durasi Sesi (Menit)')
                        ->numeric()
                        ->default(30)
                        ->required(),
                    TextInput::make('gap_minutes')
                        ->label('Jeda Persiapan (Menit)')
                        ->numeric()
                        ->default(15)
                        ->required(),
                ])
                ->action(function (array $data, SessionService $sessionService) {
                    $created = $sessionService->generateBulkDailySessions(
                        $data['date'],
                        $data['start_time'],
                        $data['end_time'],
                        (int) $data['duration_minutes'],
                        (int) $data['gap_minutes'],
                        auth()->user()
                    );

                    Notification::make()
                        ->title("Berhasil Generate {$created} Sesi")
                        ->body("Jadwal untuk tanggal {$data['date']} telah dibuat.")
                        ->success()
                        ->send();
                }),

            CreateAction::make()->label('+ Sesi Manual'),
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        return [
            'all' => Tab::make('Semua Tanggal'),
            'today' => Tab::make('Hari Ini')
                ->badge(GameSession::whereDate('date', $today)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', $today)),
            'tomorrow' => Tab::make('Besok')
                ->badge(GameSession::whereDate('date', $tomorrow)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', $tomorrow)),
            'upcoming' => Tab::make('Mendatang')
                ->badge(GameSession::whereDate('date', '>=', $today)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', '>=', $today)),
        ];
    }
}
