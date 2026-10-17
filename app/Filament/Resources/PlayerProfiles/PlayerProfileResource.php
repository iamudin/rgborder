<?php

namespace App\Filament\Resources\PlayerProfiles;

use App\Filament\Resources\PlayerProfiles\Pages\CreatePlayerProfile;
use App\Filament\Resources\PlayerProfiles\Pages\EditPlayerProfile;
use App\Filament\Resources\PlayerProfiles\Pages\ListPlayerProfiles;
use App\Models\PlayerProfile;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlayerProfileResource extends Resource
{
    protected static ?string $model = PlayerProfile::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi & Pemain';

    protected static ?string $navigationLabel = 'Database Pemain';

    protected static ?string $modelLabel = 'Profil Pemain';

    protected static ?string $pluralModelLabel = 'Daftar Pemain';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama / Callsign')
                    ->required(),
                TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->required(),
                TextInput::make('email')
                    ->label('Email'),
                Toggle::make('is_guest')
                    ->label('Pemain Walk-In / Tamu'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama / Callsign')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('Nomor WhatsApp')
                    ->searchable()
                    ->fontFamily('mono')
                    ->url(fn ($record) => 'https://wa.me/'.preg_replace('/^0/', '62', $record->phone), shouldOpenInNewTab: true),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->default('-'),

                IconColumn::make('is_guest')
                    ->label('Tamu Walk-In')
                    ->boolean(),

                TextColumn::make('booking_players_count')
                    ->label('Keikutsertaan Sesi')
                    ->counts('bookingPlayers')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_guest')
                    ->label('Tipe Pemain')
                    ->trueLabel('Hanya Walk-In')
                    ->falseLabel('Pelanggan Terdaftar (Google)'),
            ])
            ->recordActions([
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
            'index' => ListPlayerProfiles::route('/'),
            'create' => CreatePlayerProfile::route('/create'),
            'edit' => EditPlayerProfile::route('/{record}/edit'),
        ];
    }
}
