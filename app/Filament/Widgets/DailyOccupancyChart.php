<?php

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class DailyOccupancyChart extends ChartWidget
{
    protected ?string $heading = 'Tingkat Okupansi Sesi Hari Ini (Pemain vs Kapasitas)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $today = Carbon::today()->toDateString();
        $sessions = GameSession::with(['teams.bookingPlayers.booking'])
            ->where('date', $today)
            ->orderBy('start_time')
            ->get();

        $labels = [];
        $occupiedData = [];
        $maxData = [];

        foreach ($sessions as $session) {
            $labels[] = substr($session->start_time, 0, 5);
            $occupiedData[] = $session->occupied_slots_count;
            $maxData[] = $session->max_players;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemain Aktif',
                    'data' => $occupiedData,
                    'backgroundColor' => '#06b6d4',
                    'borderColor' => '#0891b2',
                ],
                [
                    'label' => 'Kapasitas Maksimal (8)',
                    'data' => $maxData,
                    'backgroundColor' => '#334155',
                    'borderColor' => '#475569',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
