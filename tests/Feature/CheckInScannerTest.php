<?php

use App\Filament\Pages\CheckInScannerPage;
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

test('public can view digital ticket via check-in url', function () {
    $customer = User::where('role', 'customer')->first();
    $session = GameSession::first();

    $booking = Booking::create([
        'booking_code' => 'RGB-TICKET-01',
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
        'name' => 'CyberHero',
        'callsign' => 'CyberHero',
        'phone' => '081234567890',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'CyberHero',
        'player_phone' => '081234567890',
        'team_id' => $session->redTeam->id,
        'check_in_token' => 'CHK-CYBER-HERO-01',
        'is_checked_in' => false,
    ]);

    $response = $this->get(route('check-in.show', $player->check_in_token));
    $response->assertStatus(200)
        ->assertSee('RGB ARENA DIGITAL PASS')
        ->assertSee('CyberHero')
        ->assertSee('BELUM CHECK-IN')
        ->assertSee('CHK-CYBER-HERO-01');
});

test('admin can confirm player check-in from digital ticket page', function () {
    $admin = User::where('role', 'admin')->first();
    $customer = User::where('role', 'customer')->first();
    $session = GameSession::first();

    $booking = Booking::create([
        'booking_code' => 'RGB-TICKET-02',
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
        'name' => 'LaserAce',
        'callsign' => 'LaserAce',
        'phone' => '081234567891',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'LaserAce',
        'player_phone' => '081234567891',
        'team_id' => $session->blueTeam->id,
        'check_in_token' => 'CHK-LASER-ACE-02',
        'is_checked_in' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('check-in.confirm', $player->check_in_token))
        ->assertRedirect(route('check-in.show', $player->check_in_token));

    $player->refresh();
    expect($player->isCheckedIn())->toBeTrue();
});

test('unauthenticated user cannot confirm check in from digital ticket page', function () {
    $session = GameSession::first();
    $customer = User::where('role', 'customer')->first();

    $booking = Booking::create([
        'booking_code' => 'RGB-TICKET-03',
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
        'name' => 'GuestPlayer',
        'callsign' => 'GuestPlayer',
        'phone' => '081234567892',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'GuestPlayer',
        'player_phone' => '081234567892',
        'team_id' => $session->redTeam->id,
        'check_in_token' => 'CHK-GUEST-03',
        'is_checked_in' => false,
    ]);

    $this->post(route('check-in.confirm', $player->check_in_token))
        ->assertRedirect(route('login'));

    $player->refresh();
    expect($player->isCheckedIn())->toBeFalse();
});

test('check in scanner filament page renders successfully for admin', function () {
    $admin = User::where('role', 'admin')->first();

    Livewire::actingAs($admin)
        ->test(CheckInScannerPage::class)
        ->assertSuccessful()
        ->assertSee('Kamera Scanner Resepsionis')
        ->assertSee('Hadir di Arena')
        ->assertSee('Menunggu Hadir')
        ->assertSee('USB Barcode Scanner Ready');
});

test('check in scanner filament page processes check in via scanned url or token', function () {
    $admin = User::where('role', 'admin')->first();
    $customer = User::where('role', 'customer')->first();
    $session = GameSession::first();
    $session->update([
        'date' => Carbon::today()->toDateString(),
        'status' => 'open',
    ]);

    $booking = Booking::create([
        'booking_code' => 'RGB-TICKET-04',
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
        'name' => 'ScannerUser',
        'callsign' => 'ScannerUser',
        'phone' => '081234567894',
    ]);

    $player = BookingPlayer::create([
        'booking_id' => $booking->id,
        'player_profile_id' => $profile->id,
        'player_name' => 'ScannerUser',
        'player_phone' => '081234567894',
        'team_id' => $session->blueTeam->id,
        'check_in_token' => 'CHK-SCANNER-TEST-04',
        'is_checked_in' => false,
    ]);

    // Test processing with scanned URL format
    $scannedUrl = "http://rgborder.test/check-in/CHK-SCANNER-TEST-04";

    Livewire::actingAs($admin)
        ->test(CheckInScannerPage::class)
        ->call('processCheckIn', $scannedUrl)
        ->assertHasNoErrors()
        ->assertSee('CHECK-IN BERHASIL!')
        ->assertSee('ScannerUser');

    $player->refresh();
    expect($player->isCheckedIn())->toBeTrue();
});
