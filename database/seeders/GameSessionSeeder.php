<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use App\Models\PlayerProfile;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameSessionSeeder extends Seeder
{
    public function run(): void
    {
        $price = Setting::get('price_per_player', 25000);
        $duration = Setting::get('session_duration_minutes', 30);
        $gap = Setting::get('inter_session_gap_minutes', 15);
        $teamCapacity = Setting::get('team_capacity', 4);
        $maxPlayers = $teamCapacity * 2;

        $customer = User::where('role', 'customer')->first();

        // Dates: Today and Tomorrow
        $dates = [Carbon::today(), Carbon::tomorrow()];

        // Start times from 13:00 to 21:00
        $times = [
            '13:00', '13:45', '14:30', '15:15', '16:00',
            '16:45', '17:30', '18:15', '19:00', '19:45', '20:30',
        ];

        foreach ($dates as $date) {
            $dateStr = $date->format('Y-m-d');
            $sessionIndex = 1;

            foreach ($times as $startTimeStr) {
                $startCarbon = Carbon::createFromFormat('Y-m-d H:i', "{$dateStr} {$startTimeStr}");
                $endCarbon = (clone $startCarbon)->addMinutes($duration);

                $sessionCode = 'SESS-'.$date->format('Ymd').'-'.str_pad($sessionIndex, 2, '0', STR_PAD_LEFT);
                $sessionIndex++;

                $session = GameSession::updateOrCreate(
                    ['session_code' => $sessionCode],
                    [
                        'date' => $dateStr,
                        'start_time' => $startCarbon->format('H:i:s'),
                        'end_time' => $endCarbon->format('H:i:s'),
                        'duration_minutes' => $duration,
                        'status' => 'open',
                        'price_per_player' => $price,
                        'max_players' => $maxPlayers,
                        'notes' => 'Sesi reguler arena laser combat',
                    ]
                );

                // Create default Red and Blue teams
                $redTeam = Team::updateOrCreate(
                    ['game_session_id' => $session->id, 'name' => 'Tim Merah'],
                    [
                        'color' => 'red',
                        'color_code' => '#EF4444',
                        'max_players' => $teamCapacity,
                    ]
                );

                $blueTeam = Team::updateOrCreate(
                    ['game_session_id' => $session->id, 'name' => 'Tim Biru'],
                    [
                        'color' => 'blue',
                        'color_code' => '#3B82F6',
                        'max_players' => $teamCapacity,
                    ]
                );
            }
        }

        // Add some realistic seed bookings on today's sessions for demo
        $todaySessions = GameSession::where('date', Carbon::today()->toDateString())->orderBy('start_time')->get();

        if ($todaySessions->count() >= 3 && $customer) {
            // Session 1: 3 players on Red, 2 on Blue (Partially filled, 3 slots available)
            $session1 = $todaySessions[0];
            $red1 = $session1->teams->firstWhere('name', 'Tim Merah');
            $blue1 = $session1->teams->firstWhere('name', 'Tim Biru');

            // Customer individual booking on Red Team
            $profile1 = PlayerProfile::firstOrCreate(
                ['user_id' => $customer->id],
                ['name' => 'CyberStriker', 'phone' => $customer->phone ?? '089876543210']
            );

            $booking1 = Booking::create([
                'booking_code' => 'RGB-'.strtoupper(Str::random(6)),
                'user_id' => $customer->id,
                'game_session_id' => $session1->id,
                'booking_type' => 'individual',
                'target_team_id' => $red1->id,
                'total_players' => 1,
                'price_per_player' => $price,
                'total_amount' => $price,
                'status' => 'confirmed',
                'notes' => 'Booking pelanggan via Google',
            ]);

            BookingPlayer::create([
                'booking_id' => $booking1->id,
                'player_profile_id' => $profile1->id,
                'team_id' => $red1->id,
                'player_name' => 'CyberStriker',
                'player_phone' => $profile1->phone,
                'check_in_status' => 'pending',
                'check_in_token' => 'CHK-'.Str::random(16),
            ]);

            Payment::create([
                'booking_id' => $booking1->id,
                'payment_method' => 'qris',
                'amount' => $price,
                'status' => 'paid',
                'reference_number' => 'QRIS-DEMO-001',
                'verified_at' => now(),
            ]);

            // Add 2 more players on Red Team (Group booking of 2)
            $p2 = PlayerProfile::create(['name' => 'ShadowSniper', 'phone' => '081234111222', 'is_guest' => true]);
            $p3 = PlayerProfile::create(['name' => 'NeonViper', 'phone' => '081234111333', 'is_guest' => true]);

            $booking2 = Booking::create([
                'booking_code' => 'RGB-'.strtoupper(Str::random(6)),
                'game_session_id' => $session1->id,
                'booking_type' => 'group',
                'target_team_id' => $red1->id,
                'total_players' => 2,
                'price_per_player' => $price,
                'total_amount' => $price * 2,
                'status' => 'confirmed',
            ]);

            BookingPlayer::create([
                'booking_id' => $booking2->id,
                'player_profile_id' => $p2->id,
                'team_id' => $red1->id,
                'player_name' => 'ShadowSniper',
                'player_phone' => $p2->phone,
                'check_in_status' => 'pending',
                'check_in_token' => 'CHK-'.Str::random(16),
            ]);

            BookingPlayer::create([
                'booking_id' => $booking2->id,
                'player_profile_id' => $p3->id,
                'team_id' => $red1->id,
                'player_name' => 'NeonViper',
                'player_phone' => $p3->phone,
                'check_in_status' => 'pending',
                'check_in_token' => 'CHK-'.Str::random(16),
            ]);

            Payment::create([
                'booking_id' => $booking2->id,
                'payment_method' => 'bank_transfer',
                'amount' => $price * 2,
                'status' => 'paid',
                'reference_number' => 'BCA-DEMO-002',
                'verified_at' => now(),
            ]);

            // Add 2 players on Blue Team
            $p4 = PlayerProfile::create(['name' => 'LaserGhost', 'phone' => '081234222444', 'is_guest' => true]);
            $p5 = PlayerProfile::create(['name' => 'ApexRider', 'phone' => '081234222555', 'is_guest' => true]);

            $booking3 = Booking::create([
                'booking_code' => 'RGB-'.strtoupper(Str::random(6)),
                'game_session_id' => $session1->id,
                'booking_type' => 'group',
                'target_team_id' => $blue1->id,
                'total_players' => 2,
                'price_per_player' => $price,
                'total_amount' => $price * 2,
                'status' => 'confirmed',
            ]);

            BookingPlayer::create([
                'booking_id' => $booking3->id,
                'player_profile_id' => $p4->id,
                'team_id' => $blue1->id,
                'player_name' => 'LaserGhost',
                'player_phone' => $p4->phone,
                'check_in_status' => 'pending',
                'check_in_token' => 'CHK-'.Str::random(16),
            ]);

            BookingPlayer::create([
                'booking_id' => $booking3->id,
                'player_profile_id' => $p5->id,
                'team_id' => $blue1->id,
                'player_name' => 'ApexRider',
                'player_phone' => $p5->phone,
                'check_in_status' => 'pending',
                'check_in_token' => 'CHK-'.Str::random(16),
            ]);

            Payment::create([
                'booking_id' => $booking3->id,
                'payment_method' => 'cash_on_site',
                'amount' => $price * 2,
                'status' => 'paid',
                'reference_number' => 'CASH-DEMO-003',
                'verified_at' => now(),
            ]);
        }
    }
}
