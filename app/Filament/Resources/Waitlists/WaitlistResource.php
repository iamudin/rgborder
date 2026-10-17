<?php

namespace App\Filament\Resources\Waitlists;

use App\Filament\Resources\Waitlists\Pages\CreateWaitlist;
use App\Filament\Resources\Waitlists\Pages\EditWaitlist;
use App\Filament\Resources\Waitlists\Pages\ListWaitlists;
use App\Models\Waitlist;
use App\Services\WaitlistService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
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

class WaitlistResource extends Resource
{
    protected static ?string $model = Waitlist::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Arena';

    protected static ?string $navigationLabel = 'Daftar Tunggu (Antrean)';

    protected static ?string $modelLabel = 'Daftar Tunggu';

    protected static ?string $pluralModelLabel = 'Antrean Sesi Penuh';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sesi & Calon Pemain')
                    ->description('Pilih sesi arena yang penuh dan data kontak pemain yang mengantre')
                    ->schema([
                        Select::make('game_session_id')
                            ->label('Sesi Permainan')
                            ->relationship('gameSession', 'session_code')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->session_code} - {$record->formatted_time_range} ({$record->date->format('d M Y')})")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('Akun Pengguna Terdaftar (Opsional)')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('player_name')
                            ->label('Nama Pemain / Callsign')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Contoh: Ghost, Soap, John'),

                        TextInput::make('player_phone')
                            ->label('Nomor WhatsApp')
                            ->required()
                            ->tel()
                            ->placeholder('081234567890'),

                        TextInput::make('player_count')
                            ->label('Jumlah Pemain')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(4)
                            ->required(),
                    ])->columns(2),

                Section::make('Status & Manajemen Antrean')
                    ->description('Pengaturan nomor urut, status antrean, dan batas waktu klaim slot')
                    ->schema([
                        TextInput::make('queue_number')
                            ->label('Nomor Antrean')
                            ->numeric()
                            ->default(fn () => (Waitlist::max('queue_number') ?? 0) + 1)
                            ->required(),

                        Select::make('status')
                            ->label('Status Antrean')
                            ->options([
                                'waiting' => 'Menunggu Slot (Waiting)',
                                'offered' => 'Slot Ditawarkan (Offered)',
                                'accepted' => 'Diterima (Accepted)',
                                'declined' => 'Ditolak (Declined)',
                                'expired' => 'Kedaluwarsa (Expired)',
                                'cancelled' => 'Dibatalkan (Cancelled)',
                            ])
                            ->default('waiting')
                            ->required(),

                        DateTimePicker::make('offer_expires_at')
                            ->label('Batas Waktu Penawaran Slot')
                            ->helperText('Batas waktu pemain merespons penawaran sebelum hangus (standar: 15 menit)'),

                        Textarea::make('notes')
                            ->label('Catatan Antrean / Petugas')
                            ->columnSpanFull()
                            ->placeholder('Catatan khusus permintaan pemain...'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('queue_number')
                    ->label('No. Antrean')
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => '#'.$state),

                TextColumn::make('gameSession.formatted_time_range')
                    ->label('Sesi Permainan')
                    ->formatStateUsing(fn ($record) => $record->gameSession?->formatted_time_range.' ('.$record->gameSession?->date?->format('d M').')'),

                TextColumn::make('player_name')
                    ->label('Nama Pemain')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('player_phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->fontFamily('mono')
                    ->url(fn ($record) => 'https://wa.me/'.preg_replace('/^0/', '62', $record->player_phone), shouldOpenInNewTab: true),

                TextColumn::make('player_count')
                    ->label('Jumlah Pemain')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status Antrean')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'offered' => 'info',
                        'accepted' => 'success',
                        'declined' => 'danger',
                        'expired' => 'gray',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('offer_expires_at')
                    ->label('Batas Penawaran')
                    ->dateTime('H:i:s (d M)')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Waktu Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('queue_number', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'waiting' => 'Menunggu Slot',
                        'offered' => 'Slot Ditawarkan (15 Menit)',
                        'accepted' => 'Diterima',
                        'expired' => 'Kedaluwarsa',
                        'cancelled' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                Action::make('offer_slot')
                    ->label('Tawarkan Slot')
                    ->icon('heroicon-o-gift')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'waiting')
                    ->action(function (Waitlist $record) {
                        $record->offerSlot(15);
                        Notification::make()->title('Slot Ditawarkan Selama 15 Menit')->success()->send();
                    }),

                Action::make('cancel_waitlist')
                    ->label('Batal')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['waiting', 'offered']))
                    ->action(function (Waitlist $record) {
                        $record->update(['status' => 'cancelled']);
                        Notification::make()->title('Antrean Dibatalkan')->info()->send();
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
            'index' => ListWaitlists::route('/'),
            'create' => CreateWaitlist::route('/create'),
            'edit' => EditWaitlist::route('/{record}/edit'),
        ];
    }
}
