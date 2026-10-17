<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArenaStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();

        $todaySessionsCount = GameSession::where('date', $today)->count();
        $playingSessionsCount = GameSession::where('status', 'playing')->count();

        $todayMaxSlots = GameSession::where('date', $today)->sum('max_players');
        $todayOccupiedPlayers = BookingPlayer::whereHas('booking', function ($q) use ($today) {
            $q->whereHas('gameSession', function ($s) use ($today) {
                $s->where('date', $today)->whereIn('status', ['open', 'full', 'ready', 'playing', 'completed']);
            })->whereIn('status', ['confirmed', 'pending_payment']);
        })->count();

        $todayEmptySlots = max(0, $todayMaxSlots - $todayOccupiedPlayers);
        $occupancyPercent = $todayMaxSlots > 0 ? round(($todayOccupiedPlayers / $todayMaxSlots) * 100, 1) : 0;

        $pendingBookingsCount = Booking::where('status', 'pending_payment')->count();
        $pendingPaymentsCount = Payment::where('status', 'pending_verification')->count();

        $todayRevenue = (int) Payment::where('status', 'paid')
            ->whereDate('verified_at', $today)
            ->sum('amount');

        $monthRevenue = (int) Payment::where('status', 'paid')
            ->whereMonth('verified_at', Carbon::now()->month)
            ->whereYear('verified_at', Carbon::now()->year)
            ->sum('amount');

        return [
            Stat::make('Sesi Hari Ini', "{$todaySessionsCount} Sesi")
                ->description("{$playingSessionsCount} Sedang Berlangsung")
                ->descriptionIcon('heroicon-m-clock')
                ->color($playingSessionsCount > 0 ? 'danger' : 'primary'),

            Stat::make('Pemain Hari Ini', "{$todayOccupiedPlayers} Orang")
                ->description("Tersisa {$todayEmptySlots} Slot Kosong ({$occupancyPercent}% Terisi)")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($occupancyPercent > 70 ? 'success' : 'info'),

            Stat::make('Perlu Verifikasi', "{$pendingPaymentsCount} Pembayaran")
                ->description("{$pendingBookingsCount} Booking Pending")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingPaymentsCount > 0 ? 'warning' : 'gray'),

            Stat::make('Pendapatan Hari Ini', 'Rp'.number_format($todayRevenue, 0, ',', '.'))
                ->description('Bulan Ini: Rp'.number_format($monthRevenue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
