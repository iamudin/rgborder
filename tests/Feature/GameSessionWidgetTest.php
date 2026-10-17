<?php

use App\Filament\Resources\GameSessions\Widgets\GameSessionStatusWidget;
use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\PlayerProfile;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed');
});

test('game session status widget renders successfully for admin with active and upcoming sessions', function () {
    $admin = User::where('role', 'admin')->first();

    // Set one session to playing today
    $today = Carbon::today()->toDateString();
    $activeSession = GameSession::first();
    $activeSession->update([
        'date' => $today,
        'start_time' => Carbon::now()->subMinutes(10)->format('H:i:s'),
        'end_time' => Carbon::now()->addMinutes(20)->format('H:i:s'),
        'status' => 'playing',
        'started_at' => Carbon::now()->subMinutes(10),
    ]);

    // Set next session
    $nextSession = GameSession::skip(1)->first();
    $nextSession->update([
        'date' => $today,
        'start_time' => Carbon::now()->addMinutes(30)->format('H:i:s'),
        'end_time' => Carbon::now()->addMinutes(60)->format('H:i:s'),
        'status' => 'open',
    ]);

    Livewire::actingAs($admin)
        ->test(GameSessionStatusWidget::class)
        ->assertSuccessful()
        ->assertSee('Pusat Komando Operasional Arena')
        ->assertSee('Scan QR Kamera / Check-In')
        ->assertSee('PERTEMPURAN AKTIF SAAT INI')
        ->assertSee('SESI PALING DEKAT BERIKUTNYA')
        ->assertSee($activeSession->session_code)
        ->assertSee($nextSession->session_code);
});

test('game session status widget can process check in via token', function () {
    $admin = User::where('role', 'admin')->first();
    $session = GameSession::first();
    $session->update([
        'date' => Carbon::today()->toDateString(),
        'status' => 'open',
    ]);

    $customer = User::where('role', 'customer')->first();
    $booking = Booking::create([
        'booking_code' => 'RGB-WIDGET-01',
        'game_session_id' => $session->id,
        'user_id' => $customer->id,
        'booking_type' => 'individual',
        'player_count' => 1,
        'price_per_player' => 25000,
        'total_amount' => 25000,
        'status' => 'confirmed',
    ]);

    $profile = PlayerProfile::create([
        'user_id' => $customer->id,
        'name' => 'ScanBot',
        'callsign' => 'ScanBot',
        'phone' => '08999999999',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'ScanBot',
        'player_phone' => '08999999999',
        'team_id' => $session->redTeam->id,
        'check_in_token' => 'TOKEN-WIDGET-TEST-1234',
        'is_checked_in' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(GameSessionStatusWidget::class)
        ->call('processCheckIn', 'TOKEN-WIDGET-TEST-1234')
        ->assertHasNoErrors();

    $player->refresh();
    expect($player->isCheckedIn())->toBeTrue();
});

test('game session status widget can process check in via booking code', function () {
    $admin = User::where('role', 'admin')->first();
    $session = GameSession::first();
    $session->update([
        'date' => Carbon::today()->toDateString(),
        'status' => 'open',
    ]);

    $customer = User::where('role', 'customer')->first();
    $booking = Booking::create([
        'booking_code' => 'RGB-WIDGET-02',
        'game_session_id' => $session->id,
        'user_id' => $customer->id,
        'booking_type' => 'individual',
        'player_count' => 1,
        'price_per_player' => 25000,
        'total_amount' => 25000,
        'status' => 'confirmed',
    ]);

    $profile = PlayerProfile::create([
        'user_id' => $customer->id,
        'name' => 'CodeBot',
        'callsign' => 'CodeBot',
        'phone' => '08888888888',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'CodeBot',
        'player_phone' => '08888888888',
        'team_id' => $session->blueTeam->id,
        'check_in_token' => 'TOKEN-WIDGET-TEST-5678',
        'is_checked_in' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(GameSessionStatusWidget::class)
        ->call('processCheckIn', 'RGB-WIDGET-02')
        ->assertHasNoErrors();

    $player->refresh();
    expect($player->isCheckedIn())->toBeTrue();
});

test('game session status widget can start and finish game session', function () {
    $admin = User::where('role', 'admin')->first();
    $session = GameSession::first();
    $session->update([
        'status' => 'ready',
    ]);

    // Add a confirmed player so the session can start
    $customer = User::where('role', 'customer')->first();
    $booking = Booking::create([
        'booking_code' => 'RGB-WIDGET-START',
        'game_session_id' => $session->id,
        'user_id' => $customer->id,
        'booking_type' => 'individual',
        'player_count' => 1,
        'price_per_player' => 25000,
        'total_amount' => 25000,
        'status' => 'confirmed',
    ]);

    $profile = PlayerProfile::create([
        'user_id' => $customer->id,
        'name' => 'StartBot',
        'callsign' => 'StartBot',
        'phone' => '08777777777',
    ]);

    BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'StartBot',
        'player_phone' => '08777777777',
        'team_id' => $session->redTeam->id,
        'check_in_token' => 'TOKEN-START-TEST',
        'is_checked_in' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(GameSessionStatusWidget::class)
        ->call('startGameSession', $session->id);

    $session->refresh();
    expect($session->status)->toBe('playing')
        ->and($session->started_at)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test(GameSessionStatusWidget::class)
        ->call('finishGameSession', $session->id);

    $session->refresh();
    expect($session->status)->toBe('completed')
        ->and($session->ended_at)->not->toBeNull();
});
