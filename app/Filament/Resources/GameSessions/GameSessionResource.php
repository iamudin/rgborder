<?php

namespace App\Filament\Resources\GameSessions;

use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Pages\SessionDetail;
use App\Models\GameSession;
use App\Services\GameSessionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameSessionResource extends Resource
{
    protected static ?string $model = GameSession::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional Arena';

    protected static ?string $navigationLabel = 'Jadwal & Sesi';

    protected static ?string $modelLabel = 'Sesi Permainan';

    protected static ?string $pluralModelLabel = 'Jadwal Pertempuran';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->required(),
                TextInput::make('duration_minutes')
                    ->label('Durasi (Menit)')
                    ->numeric()
                    ->default(30)
                    ->required(),
                TextInput::make('price_per_player')
                    ->label('Harga / Pemain (Rp)')
                    ->numeric()
                    ->default(25000)
                    ->prefix('Rp')
                    ->required(),
                Select::make('status')
                    ->label('Status Sesi')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'full' => 'Full',
                        'ready' => 'Ready (Siap)',
                        'playing' => 'Playing (Berlangsung)',
                        'completed' => 'Completed (Selesai)',
                        'cancelled' => 'Cancelled (Batal)',
                    ])
                    ->default('open')
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan Petugas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_code')
                    ->label('Kode Sesi')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Jam Permainan')
                    ->formatStateUsing(fn ($record) => $record->formatted_time_range.' WIB')
                    ->fontFamily('mono'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'open' => 'success',
                        'full' => 'warning',
                        'ready' => 'info',
                        'playing' => 'danger',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('occupied_slots_count')
                    ->label('Pemain')
                    ->formatStateUsing(fn ($record) => $record->occupied_slots_count.' / '.$record->max_players)
                    ->badge(),

                TextColumn::make('available_slots_count')
                    ->label('Slot Sisa')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('confirmed_revenue')
                    ->label('Pendapatan')
                    ->money('IDR', locale: 'id'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Filter::make('date')
                    ->label('Filter Tanggal')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal Sesi')
                            ->placeholder('Pilih tanggal tertentu...'),
                        DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->placeholder('Mulai tanggal...'),
                        DatePicker::make('date_until')
                            ->label('Sampai Tanggal')
                            ->placeholder('Hingga tanggal...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['date'] ?? null),
                                fn (Builder $query) => $query->whereDate('date', $data['date'])
                            )
                            ->when(
                                blank($data['date'] ?? null) && filled($data['date_from'] ?? null),
                                fn (Builder $query) => $query->whereDate('date', '>=', $data['date_from'])
                            )
                            ->when(
                                blank($data['date'] ?? null) && filled($data['date_until'] ?? null),
                                fn (Builder $query) => $query->whereDate('date', '<=', $data['date_until'])
                            );
                    })
                    ->indicateUsing(function (array $state): array {
                        $indicators = [];

                        if (filled($state['date'] ?? null)) {
                            $indicators[] = 'Tanggal: ' . Carbon::parse($state['date'])->translatedFormat('d M Y');
                        } else {
                            if (filled($state['date_from'] ?? null)) {
                                $indicators[] = 'Dari: ' . Carbon::parse($state['date_from'])->translatedFormat('d M Y');
                            }

                            if (filled($state['date_until'] ?? null)) {
                                $indicators[] = 'Sampai: ' . Carbon::parse($state['date_until'])->translatedFormat('d M Y');
                            }
                        }

                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'full' => 'Full',
                        'ready' => 'Ready',
                        'playing' => 'Playing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('session_detail')
                    ->label('Ruang Kontrol')
                    ->icon('heroicon-o-computer-desktop')
                    ->url(fn ($record) => SessionDetail::getUrl(['record' => $record]))
                    ->color('primary'),

                Action::make('start')
                    ->label('Mulai')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['open', 'full', 'ready']) && $record->confirmed_slots_count > 0)
                    ->action(function (GameSession $record, GameSessionService $service) {
                        $service->startGame($record, auth()->user());
                        Notification::make()->title('Pertempuran Dimulai!')->success()->send();
                    }),

                Action::make('finish')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->isPlaying())
                    ->action(function (GameSession $record, GameSessionService $service) {
                        $service->finishGame($record, 'Selesai dari tabel admin', auth()->user());
                        Notification::make()->title('Sesi Ditandai Selesai')->info()->send();
                    }),

                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameSessions::route('/'),
            'create' => CreateGameSession::route('/create'),
            'edit' => EditGameSession::route('/{record}/edit'),
            'session-detail' => SessionDetail::route('/{record}/detail'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\GameSessions\Widgets\GameSessionStatusWidget::class,
        ];
    }
}
