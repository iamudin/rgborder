<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan & Laporan';

    protected static ?string $navigationLabel = 'Verifikasi Pembayaran';

    protected static ?string $modelLabel = 'Transaksi Pembayaran';

    protected static ?string $pluralModelLabel = 'Daftar Pembayaran';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi Booking')
                    ->description('Hubungkan pembayaran dengan kode booking yang valid')
                    ->schema([
                        Select::make('booking_id')
                            ->label('Kode Booking')
                            ->relationship('booking', 'booking_code')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->booking_code} - " . ($record->user?->name ?? 'Walk-In') . " ({$record->gameSession?->formatted_time_range})")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'qris' => 'QRIS Arena',
                                'bank_transfer' => 'Transfer Bank (BCA)',
                                'cash_on_site' => 'Tunai di Kasir (Cash on Site)',
                            ])
                            ->default('qris')
                            ->required(),

                        TextInput::make('amount')
                            ->label('Nominal Pembayaran (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'unpaid' => 'Belum Bayar (Unpaid)',
                                'pending_verification' => 'Menunggu Verifikasi (Pending)',
                                'paid' => 'Lunas Terverifikasi (Paid)',
                                'failed' => 'Gagal (Failed)',
                                'expired' => 'Kedaluwarsa (Expired)',
                                'refunded' => 'Dikembalikan (Refunded)',
                            ])
                            ->default('pending_verification')
                            ->required(),
                    ])->columns(2),

                Section::make('Bukti & Verifikasi Pembayaran')
                    ->description('File bukti transfer dan informasi petugas verifikator')
                    ->schema([
                        FileUpload::make('proof_file')
                            ->label('File Bukti Pembayaran / Struk')
                            ->disk('public')
                            ->directory('payments')
                            ->image()
                            ->maxSize(5120),

                        TextInput::make('reference_number')
                            ->label('Nomor Referensi Transaksi')
                            ->placeholder('Contoh: TR-20261016-001'),

                        DateTimePicker::make('verified_at')
                            ->label('Waktu Verifikasi'),

                        Select::make('verified_by')
                            ->label('Petugas Verifikator')
                            ->relationship('verifiedBy', 'name')
                            ->searchable()
                            ->preload(),

                        Textarea::make('notes')
                            ->label('Catatan Transaksi / Pembayaran')
                            ->columnSpanFull()
                            ->placeholder('Catatan verifikasi atau keterangan admin...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('booking.user.name')
                    ->label('Pelanggan')
                    ->default('Walk-In')
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'qris' => 'QRIS Arena',
                        'bank_transfer' => 'Transfer Bank',
                        'cash_on_site' => 'Bayar di Lokasi (Cash)',
                        default => $state,
                    }),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending_verification' => 'warning',
                        'unpaid' => 'danger',
                        'failed' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper(str_replace('_', ' ', $state))),

                ImageColumn::make('proof_file')
                    ->label('Bukti')
                    ->disk('public'),

                TextColumn::make('reference_number')
                    ->label('No. Ref')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('verified_at')
                    ->label('Diverifikasi')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),

                TextColumn::make('verifiedBy.name')
                    ->label('Petugas')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending_verification' => 'Menunggu Verifikasi',
                        'paid' => 'Lunas (Paid)',
                        'unpaid' => 'Belum Dibayar',
                        'expired' => 'Kedaluwarsa',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'qris' => 'QRIS',
                        'bank_transfer' => 'Transfer Bank',
                        'cash_on_site' => 'Bayar di Lokasi',
                    ]),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Setujui (Lunas)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending_verification', 'unpaid']))
                    ->action(function (Payment $record, PaymentService $paymentService) {
                        $paymentService->verifyPayment($record, auth()->user(), 'ADM-CONFIRM');
                        Notification::make()->title('Pembayaran Berhasil Diverifikasi')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Tolak Bukti')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending_verification')
                    ->action(function (Payment $record, PaymentService $paymentService) {
                        $paymentService->rejectPayment($record, 'Bukti tidak valid atau tidak terbaca', auth()->user());
                        Notification::make()->title('Pembayaran Ditolak')->warning()->send();
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
