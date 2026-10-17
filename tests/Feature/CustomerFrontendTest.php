<?php

use App\Models\Booking;
use App\Models\GameSession;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->artisan('db:seed');
    GameSession::where('status', 'open')->update([
        'date' => Carbon::tomorrow()->toDateString(),
    ]);
});

test('public pages load successfully', function () {
    $this->get('/')->assertStatus(200)->assertSee('RGB ARENA');
    $this->get('/jadwal')->assertStatus(200)->assertSee('Jadwal Permainan');
    $this->get('/login')->assertStatus(200)->assertSee('MASUK KE RGB ARENA');
});

test('development login logs in user and redirects to dashboard', function () {
    $response = $this->post('/auth/dev-login', [
        'email' => 'player@rgbarena.com',
        'name' => 'CyberStriker',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

test('booking index automatically selects closest upcoming session and ignores past hours', function () {
    $today = Carbon::today()->toDateString();

    // 1. Session in the past today (empty/open, but 2 hours ago)
    $pastSession = GameSession::create([
        'session_code' => 'SESS-TEST-PAST',
        'date' => $today,
        'start_time' => Carbon::now()->subHours(2)->format('H:i:s'),
        'end_time' => Carbon::now()->subHours(1)->subMinutes(30)->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => 'open',
        'price_per_player' => 25000,
        'max_players' => 8,
    ]);
    Team::create(['game_session_id' => $pastSession->id, 'name' => 'Tim Merah', 'color' => 'red', 'color_code' => '#EF4444', 'max_players' => 4]);
    Team::create(['game_session_id' => $pastSession->id, 'name' => 'Tim Biru', 'color' => 'blue', 'color_code' => '#3B82F6', 'max_players' => 4]);

    // 2. Closest upcoming session today (1 hour in the future)
    $upcomingSession = GameSession::create([
        'session_code' => 'SESS-TEST-NEXT',
        'date' => $today,
        'start_time' => Carbon::now()->addHours(1)->format('H:i:s'),
        'end_time' => Carbon::now()->addHours(1)->addMinutes(30)->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => 'open',
        'price_per_player' => 25000,
        'max_players' => 8,
    ]);
    Team::create(['game_session_id' => $upcomingSession->id, 'name' => 'Tim Merah', 'color' => 'red', 'color_code' => '#EF4444', 'max_players' => 4]);
    Team::create(['game_session_id' => $upcomingSession->id, 'name' => 'Tim Biru', 'color' => 'blue', 'color_code' => '#3B82F6', 'max_players' => 4]);

    // Visiting /booking must redirect to $upcomingSession, NOT $pastSession
    $response = $this->get('/booking');
    $response->assertRedirect(route('booking.show', $upcomingSession));

    // Trying to visit the past session directly redirects to closest bookable session with a warning
    $pastResponse = $this->get(route('booking.show', $pastSession));
    $pastResponse->assertRedirect(route('booking.show', $upcomingSession));
    $pastResponse->assertSessionHas('warning');
});

test('customer can create individual booking', function () {
    $user = User::factory()->create(['phone' => '081234567890']);
    $session = GameSession::where('status', 'open')->first();

    $bookingService = app(BookingService::class);
    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'GamerPro', 'phone' => '081234567890']],
        null,
        'qris'
    );

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->status)->toBe('pending_payment')
        ->and($booking->total_players)->toBe(1)
        ->and($booking->total_amount)->toBe($session->price_per_player)
        ->and($booking->bookingPlayers)->toHaveCount(1);
});

test('group booking keeps all players in the same team', function () {
    $user = User::factory()->create(['phone' => '081111111111']);
    $session = GameSession::where('status', 'open')->first();

    $bookingService = app(BookingService::class);
    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'group',
        [
            ['name' => 'Leader', 'phone' => '081111111111'],
            ['name' => 'Mate1', 'phone' => '081111111112'],
            ['name' => 'Mate2', 'phone' => '081111111113'],
        ],
        null,
        'qris'
    );

    $teamIds = $booking->bookingPlayers->pluck('team_id')->unique();
    expect($teamIds)->toHaveCount(1);
});

test('prevents duplicate booking for the same player phone in same session', function () {
    $user = User::factory()->create(['phone' => '089999999999']);
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);

    $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'FirstBooking', 'phone' => '089999999999']],
        null,
        'qris'
    );

    expect(function () use ($bookingService, $user, $session) {
        $bookingService->createCustomerBooking(
            $user,
            $session->id,
            'individual',
            [['name' => 'SecondBooking', 'phone' => '089999999999']],
            null,
            'qris'
        );
    })->toThrow(ValidationException::class);
});

test('expire stale holds releases slots and marks booking as expired', function () {
    $user = User::factory()->create(['phone' => '087777777777']);
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);

    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'LatePayer', 'phone' => '087777777777']],
        null,
        'qris'
    );

    // Simulate hold expiration
    $booking->update(['expires_at' => Carbon::now()->subMinutes(5)]);

    $count = $bookingService->expireStaleHolds();
    expect($count)->toBeGreaterThanOrEqual(1);

    $booking->refresh();
    expect($booking->status)->toBe('expired');
});

test('homepage renders modern hero parallax slider with transparent hexagonal steel overlay', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('hex-steel-overlay');
    $response->assertSee('hex-steel-vignette');
    $response->assertSee('Zona Tempur Sektor Merah');
    $response->assertSee('Zona Tempur Sektor Biru');
});

test('homepage renders youtube video trailer and google maps location embed section', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Trailer Keseruan Arena RGB');
    $response->assertSee('Peta & Rute Tempur Arena', false);
    $response->assertSee('youtube-nocookie.com/embed');
    $response->assertSee('google.com/maps/embed');
    $response->assertSee('Buka Rute di Google Maps');
    $response->assertDontSee('Sensor Presisi');
});

test('admin settings page allows configuring hero slider and adding custom slides', function () {
    \Livewire\Livewire::test(\App\Filament\Pages\SettingsPage::class)
        ->assertSet('hero_slider_enabled', true)
        ->set('new_slide_url', 'images/hero/custom-sector.jpg')
        ->set('new_slide_title', 'Sektor Delta Arena')
        ->set('new_slide_tagline', 'SPECIAL TACTICAL ZONE')
        ->set('new_slide_badge', 'VIP SQUAD')
        ->set('new_slide_badge_color', 'purple')
        ->call('addSlide')
        ->assertHasNoErrors()
        ->set('youtube_trailer_url', 'https://youtu.be/custom12345')
        ->set('arena_address', 'Jl. Jenderal Sudirman No. 99, Jakarta')
        ->call('save')
        ->assertHasNoErrors();

    $slides = \App\Models\Setting::get('hero_slides', []);
    expect(collect($slides)->contains('title', 'Sektor Delta Arena'))->toBeTrue()
        ->and(\App\Models\Setting::get('youtube_trailer_url'))->toBe('https://youtu.be/custom12345')
        ->and(\App\Models\Setting::get('arena_address'))->toBe('Jl. Jenderal Sudirman No. 99, Jakarta');
});

test('schedule page displays non-draft statuses in the filter tabs and hides draft sessions', function () {
    $testDate = Carbon::tomorrow()->toDateString();
    GameSession::whereDate('date', $testDate)->delete();

    // Create sessions with different statuses on testDate, including a draft session
    $statuses = ['draft', 'open', 'full', 'ready', 'playing', 'completed', 'cancelled'];
    foreach ($statuses as $idx => $st) {
        $sess = GameSession::create([
            'session_code' => 'STATUS-TEST-' . strtoupper($st),
            'date' => $testDate,
            'start_time' => sprintf('%02d:00:00', 10 + $idx),
            'end_time' => sprintf('%02d:30:00', 10 + $idx),
            'duration_minutes' => 30,
            'status' => $st,
            'price_per_player' => 25000,
            'max_players' => 8,
        ]);
        Team::create(['game_session_id' => $sess->id, 'name' => 'Tim Merah', 'color' => 'red', 'color_code' => '#EF4444', 'max_players' => 4]);
        Team::create(['game_session_id' => $sess->id, 'name' => 'Tim Biru', 'color' => 'blue', 'color_code' => '#3B82F6', 'max_players' => 4]);
    }

    $response = $this->get('/jadwal?date=' . $testDate);

    $response->assertStatus(200);

    // Assert active/published status tabs and filter pills exist
    $response->assertSee('Semua Status');
    $response->assertSee('Open (Terbuka)');
    $response->assertSee('Full (Penuh)');
    $response->assertSee('Ready (Siap)');
    $response->assertSee('Playing (Berlangsung)');
    $response->assertSee('Completed (Selesai)');
    $response->assertSee('Cancelled (Batal)');

    // Assert draft is NOT shown in public filter or cards
    $response->assertDontSee('STATUS-TEST-DRAFT');
    $response->assertDontSee('DRAFT (PERSIAPAN)');
    $response->assertDontSee('Belum Dibuka (Draft)');

    // Assert badges for visible active statuses
    $response->assertSee('CANCELLED (BATAL)');
    $response->assertSee('PLAYING (BERLANGSUNG)');
    $response->assertSee('COMPLETED (SELESAI)');
    $response->assertSee('READY (SIAP TEMPUR)');
    $response->assertSee('FULL (PENUH)');
    $response->assertSee('OPEN (SLOT TERBUKA)');

    // Assert CTA button states
    $response->assertSee('Sesi Dibatalkan');
    $response->assertSee('Pertempuran Berlangsung');
    $response->assertSee('Pemain Siap / Briefing');
    $response->assertSee('Gabung Permainan');
});


