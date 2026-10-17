<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi & Pemain';

    protected static ?string $navigationLabel = 'Semua Booking';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Daftar Booking';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sesi & Pemesan')
                    ->description('Detail sesi permainan dan akun pemesan')
                    ->schema([
                        TextInput::make('booking_code')
                            ->label('Kode Booking')
                            ->default(fn () => 'RGB-'.strtoupper(\Illuminate\Support\Str::random(6)))
                            ->required()
                            ->readOnly(),

                        Select::make('game_session_id')
                            ->label('Sesi Permainan')
                            ->relationship('gameSession', 'session_code')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->session_code} - {$record->formatted_time_range} ({$record->date->format('d M Y')})")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('Akun Pengguna (Opsional)')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('booking_type')
                            ->label('Jenis Booking')
                            ->options([
                                'individual' => 'Individual (1 Pemain)',
                                'group' => 'Kelompok (2-4 Pemain)',
                                'full_team' => 'Tim Penuh (4 Pemain)',
                            ])
                            ->default('individual')
                            ->required(),

                        Select::make('target_team_id')
                            ->label('Pilihan Tim (Opsional)')
                            ->relationship('targetTeam', 'name'),
                    ])->columns(2),

                Section::make('Rincian Pemain & Pembayaran')
                    ->description('Jumlah pemain, snapshot tarif per orang, total tagihan, dan status transaksi')
                    ->schema([
                        TextInput::make('total_players')
                            ->label('Jumlah Pemain')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(4)
                            ->required(),

                        TextInput::make('price_per_player')
                            ->label('Harga / Pemain (Rp)')
                            ->numeric()
                            ->default(25000)
                            ->prefix('Rp')
                            ->required(),

                        TextInput::make('total_amount')
                            ->label('Total Tagihan (Rp)')
                            ->numeric()
                            ->default(25000)
                            ->prefix('Rp')
                            ->required(),

                        Select::make('status')
                            ->label('Status Booking')
                            ->options([
                                'pending_payment' => 'Menunggu Pembayaran',
                                'confirmed' => 'Terkonfirmasi (Confirmed)',
                                'cancelled' => 'Dibatalkan',
                                'expired' => 'Kedaluwarsa',
                            ])
                            ->default('confirmed')
                            ->required(),

                        Textarea::make('notes')
                            ->label('Catatan Petugas')
                            ->columnSpanFull()
                            ->placeholder('Catatan atau permintaan khusus pelanggan...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('user.name')
                    ->label('Pemesan')
                    ->default('Walk-In')
                    ->searchable(),

                TextColumn::make('gameSession.date')
                    ->label('Tanggal Sesi')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('gameSession.start_time')
                    ->label('Jam')
                    ->formatStateUsing(fn ($record) => $record->gameSession?->formatted_time_range)
                    ->fontFamily('mono'),

                TextColumn::make('booking_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'individual' => 'Individual (1)',
                        'group' => 'Kelompok',
                        'full_team' => 'Tim Penuh (4)',
                        default => $state,
                    }),

                TextColumn::make('total_players')
                    ->label('Pemain')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total (Rp)')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'pending_payment' => 'warning',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper(str_replace('_', ' ', $state))),

                TextColumn::make('created_at')
                    ->label('Waktu Pesan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Menunggu Pembayaran',
                        'confirmed' => 'Terkonfirmasi',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kedaluwarsa',
                    ]),
            ])
            ->recordActions([
                Action::make('confirm_payment')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending_payment')
                    ->action(function (Booking $record, PaymentService $paymentService) {
                        $payment = $record->latestPayment;
                        if ($payment) {
                            $paymentService->verifyPayment($payment, auth()->user(), 'MANUAL-ADMIN');
                            Notification::make()->title('Booking Berhasil Dikonfirmasi')->success()->send();
                        }
                    }),

                Action::make('cancel_booking')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending_payment', 'confirmed']))
                    ->action(function (Booking $record, BookingService $bookingService) {
                        $bookingService->cancelBooking($record, 'Dibatalkan oleh Admin di Panel', auth()->user());
                        Notification::make()->title('Booking Telah Dibatalkan')->warning()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
