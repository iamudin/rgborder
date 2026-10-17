<?php

use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use App\Models\PlayerProfile;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use App\Models\Waitlist;
use App\Services\BookingService;
use App\Services\CheckInService;
use App\Services\GameSessionService;
use App\Services\PaymentService;
use App\Services\TeamAssignmentService;
use App\Services\WaitlistService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->artisan('db:seed');
    GameSession::where('status', 'open')->update([
        'date' => Carbon::tomorrow()->toDateString(),
    ]);
});

// 1. Hak Akses Admin dan Pelanggan ke Panel Admin
test('admin user can access filament admin panel but customer is forbidden', function () {
    $admin = User::where('role', 'admin')->first();
    $customer = User::where('role', 'customer')->first();

    $panel = Filament::getPanel('admin');

    expect($admin->canAccessPanel($panel))->toBeTrue()
        ->and($customer->canAccessPanel($panel))->toBeFalse();
});

test('session detail control room page renders with 200 ok for admin', function () {
    $admin = User::where('role', 'admin')->first();
    $session = GameSession::first();

    $this->actingAs($admin)
        ->get("/admin/game-sessions/{$session->id}/detail")
        ->assertStatus(200)
        ->assertSee('Timer Permainan 30 Menit')
        ->assertSee('Tim Merah')
        ->assertSee('Tim Biru');
});

test('settings page renders with 200 ok for admin', function () {
    $admin = User::where('role', 'admin')->first();

    $this->actingAs($admin)
        ->get('/admin/settings-page')
        ->assertStatus(200)
        ->assertSee('Informasi Umum Arena')
        ->assertSee('Parameter Bisnis');
});

// 2. Booking Individual
test('individual booking creates single slot and snapshot price', function () {
    $user = User::factory()->create(['phone' => '081200000001']);
    $session = GameSession::where('status', 'open')->first();
    $service = app(BookingService::class);

    $booking = $service->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'SoloPlayer', 'phone' => '081200000001']],
        null,
        'qris'
    );

    expect($booking->status)->toBe('pending_payment')
        ->and($booking->total_players)->toBe(1)
        ->and($booking->total_amount)->toBe($session->price_per_player)
        ->and($booking->bookingPlayers)->toHaveCount(1);
});

// 3. Booking Kelompok (Semua anggota ditempatkan pada satu tim yang sama)
test('group booking assigns all players to the exact same team', function () {
    $user = User::factory()->create(['phone' => '081200000002']);
    $session = GameSession::where('status', 'open')->first();
    $service = app(BookingService::class);

    $booking = $service->createCustomerBooking(
        $user,
        $session->id,
        'group',
        [
            ['name' => 'Cap', 'phone' => '081200000002'],
            ['name' => 'MateA', 'phone' => '081200000003'],
            ['name' => 'MateB', 'phone' => '081200000004'],
        ],
        null,
        'qris'
    );

    $teamIds = $booking->bookingPlayers->pluck('team_id')->unique();
    expect($teamIds)->toHaveCount(1)
        ->and($booking->total_players)->toBe(3)
        ->and($booking->total_amount)->toBe($session->price_per_player * 3);
});

// 4. Booking Tim Penuh (4 Pemain dalam satu tim)
test('full team booking reserves 4 slots in one team for 100k', function () {
    $user = User::factory()->create(['phone' => '081200000010']);
    $session = GameSession::where('status', 'open')->first();
    $service = app(BookingService::class);

    $booking = $service->createCustomerBooking(
        $user,
        $session->id,
        'full_team',
        [
            ['name' => 'P1', 'phone' => '081200000011'],
            ['name' => 'P2', 'phone' => '081200000012'],
            ['name' => 'P3', 'phone' => '081200000013'],
            ['name' => 'P4', 'phone' => '081200000014'],
        ],
        null,
        'bank_transfer'
    );

    expect($booking->total_players)->toBe(4)
        ->and($booking->total_amount)->toBe($session->price_per_player * 4)
        ->and($booking->bookingPlayers->pluck('team_id')->unique())->toHaveCount(1);
});

// 5. Booking saat sesi penuh ditolak
test('booking rejects when session is full', function () {
    $session = GameSession::where('status', 'open')->first();
    // Fill all slots
    $session->update(['status' => 'full']);

    $user = User::factory()->create(['phone' => '081200000020']);
    $service = app(BookingService::class);

    expect(function () use ($service, $user, $session) {
        $service->createCustomerBooking(
            $user,
            $session->id,
            'individual',
            [['name' => 'LatePlayer', 'phone' => '081200000020']],
            null,
            'qris'
        );
    })->toThrow(ValidationException::class);
});

// 6. Pencegahan Double Booking untuk pemain yang sama pada sesi yang sama
test('prevents double booking for the same phone in the same session', function () {
    $session = GameSession::where('status', 'open')->first();
    $service = app(BookingService::class);
    $user1 = User::factory()->create(['phone' => '081200000030']);
    $user2 = User::factory()->create(['phone' => '081200000031']);

    $service->createCustomerBooking(
        $user1,
        $session->id,
        'individual',
        [['name' => 'SameGamer', 'phone' => '081200000030']],
        null,
        'qris'
    );

    expect(function () use ($service, $user2, $session) {
        $service->createCustomerBooking(
            $user2,
            $session->id,
            'individual',
            [['name' => 'AnotherGuyRegisteringSameGamer', 'phone' => '081200000030']],
            null,
            'qris'
        );
    })->toThrow(ValidationException::class);
});

// 7. Temporary Hold dan Pembayaran Pending
test('booking hold is temporary and payment status is unpaid initially', function () {
    $user = User::factory()->create(['phone' => '081200000040']);
    $session = GameSession::where('status', 'open')->first();
    $service = app(BookingService::class);

    $booking = $service->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'Tester', 'phone' => '081200000040']],
        null,
        'qris'
    );

    expect($booking->status)->toBe('pending_payment')
        ->and($booking->expires_at)->not->toBeNull()
        ->and($booking->expires_at->isFuture())->toBeTrue()
        ->and($booking->latestPayment->status)->toBe('unpaid');
});

// 8. Verifikasi Pembayaran Mengubah Status Menjadi Confirmed
test('payment verification confirms the booking and releases hold timer', function () {
    $user = User::factory()->create(['phone' => '081200000050']);
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);
    $paymentService = app(PaymentService::class);
    $admin = User::where('role', 'admin')->first();

    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'Payer', 'phone' => '081200000050']],
        null,
        'qris'
    );

    $payment = $booking->latestPayment;
    $paymentService->verifyPayment($payment, $admin, 'REF-999');

    $booking->refresh();
    $payment->refresh();

    expect($booking->status)->toBe('confirmed')
        ->and($booking->expires_at)->toBeNull()
        ->and($payment->status)->toBe('paid')
        ->and($payment->verified_by)->toBe($admin->id);
});

// 9. Pemindahan Tim Memvalidasi Kapasitas dan Mencatat Log
test('moving player to full team throws validation error', function () {
    $session = GameSession::where('status', 'open')->first();
    $redTeam = $session->redTeam;
    $blueTeam = $session->blueTeam;

    // Make blue team full (capacity 4)
    $blueTeam->update(['max_players' => 0]);

    $user = User::factory()->create(['phone' => '081200000060']);
    $bookingService = app(BookingService::class);
    $teamService = app(TeamAssignmentService::class);

    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'PlayerToMove', 'phone' => '081200000060']],
        $redTeam->id,
        'qris'
    );

    $player = $booking->bookingPlayers->first();

    expect(function () use ($teamService, $player, $blueTeam) {
        $teamService->movePlayer($player, $blueTeam);
    })->toThrow(ValidationException::class);
});

// 10. Check-in Menggunakan Unique Token Sekali Pakai
test('check-in uses unique token and cannot be used twice', function () {
    $user = User::factory()->create(['phone' => '081200000070']);
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);
    $checkInService = app(CheckInService::class);

    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'CheckedPlayer', 'phone' => '081200000070']],
        null,
        'cash_on_site'
    );

    // Confirm booking so ticket is ready
    $booking->update(['status' => 'confirmed']);

    $player = $booking->bookingPlayers->first();
    $token = $player->check_in_token;

    expect($token)->not->toBeEmpty();

    // First check-in succeeds
    $checkedPlayer = $checkInService->checkInByToken($token);
    expect($checkedPlayer->isCheckedIn())->toBeTrue();

    // Second check-in with same token fails
    expect(function () use ($checkInService, $token) {
        $checkInService->checkInByToken($token);
    })->toThrow(ValidationException::class);
});

// 11. Pembatalan Booking Melepaskan Slot
test('booking cancellation marks booking as cancelled', function () {
    $user = User::factory()->create(['phone' => '081200000080']);
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);

    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'CancelMe', 'phone' => '081200000080']],
        null,
        'qris'
    );

    $result = $bookingService->cancelBooking($booking, 'Batal sendiri');
    expect($result)->toBeTrue();

    $booking->refresh();
    expect($booking->status)->toBe('cancelled');
});

// 12. Daftar Tunggu (Waitlist) dan Penawaran Slot
test('waitlist queues player and offers slot with 15-minute expiry', function () {
    $session = GameSession::where('status', 'open')->first();
    $user = User::factory()->create(['phone' => '081200000090']);
    $waitlistService = app(WaitlistService::class);

    $waitlist = $waitlistService->joinWaitlist(
        $session,
        $user,
        'WaitlistHero',
        '081200000090',
        2
    );

    expect($waitlist->status)->toBe('waiting')
        ->and($waitlist->queue_number)->toBeGreaterThanOrEqual(1);

    // Offer slot
    $waitlistService->checkAndOfferAvailableSlots($session);
    $waitlist->refresh();

    expect($waitlist->status)->toBe('offered')
        ->and($waitlist->offer_expires_at)->not->toBeNull()
        ->and($waitlist->offer_expires_at->isFuture())->toBeTrue();
});

// 13. Game Timer Server 30 Menit
test('game session timer calculates elapsed and remaining time accurately from server', function () {
    $session = GameSession::where('status', 'open')->first();
    $gameService = app(GameSessionService::class);
    $admin = User::where('role', 'admin')->first();

    // Add a confirmed booking so it can start
    $user = User::factory()->create(['phone' => '081200000100']);
    $bookingService = app(BookingService::class);
    $booking = $bookingService->createCustomerBooking(
        $user,
        $session->id,
        'individual',
        [['name' => 'Fighter', 'phone' => '081200000100']],
        null,
        'qris'
    );
    $booking->update(['status' => 'confirmed']);

    $gameService->startGame($session, $admin);

    $session->refresh();
    expect($session->isPlaying())->toBeTrue()
        ->and($session->started_at)->not->toBeNull()
        ->and($session->getRemainingSeconds())->toBeGreaterThan(0)
        ->and($session->getRemainingSeconds())->toBeLessThanOrEqual(30 * 60);

    // Simulating time passed: 31 minutes ago
    $session->update(['started_at' => Carbon::now()->subMinutes(31)]);
    expect($session->getRemainingSeconds())->toBe(0);

    // Auto-complete check completes it
    $count = $gameService->checkAndAutoCompletePlayingSessions();
    expect($count)->toBe(1);

    $session->refresh();
    expect($session->isCompleted())->toBeTrue();
});

// 14. Pemain Walk-In Tidak Butuh Akun Google
test('walk-in booking created by admin creates guest player profile without user account', function () {
    $session = GameSession::where('status', 'open')->first();
    $bookingService = app(BookingService::class);
    $admin = User::where('role', 'admin')->first();

    $booking = $bookingService->createWalkInBooking(
        $session,
        [['name' => 'GuestGamer', 'phone' => '081200000110']],
        null,
        'cash_on_site',
        true,
        $admin
    );

    expect($booking->user_id)->toBeNull()
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->bookingPlayers->first()->playerProfile->is_guest)->toBeTrue();
});

// 15. Form Resource Admin Filament (Waitlist, Booking, Payment) Memuat Komponen Lengkap
test('filament admin create forms render complete schema components', function () {
    $admin = User::where('role', 'admin')->first();

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Filament\Resources\Waitlists\Pages\CreateWaitlist::class)
        ->assertSuccessful()
        ->assertSee('Sesi Permainan')
        ->assertSee('Nama Pemain / Callsign')
        ->assertSee('Nomor WhatsApp')
        ->assertSee('Nomor Antrean');

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Filament\Resources\Bookings\Pages\CreateBooking::class)
        ->assertSuccessful()
        ->assertSee('Kode Booking')
        ->assertSee('Sesi Permainan')
        ->assertSee('Jumlah Pemain')
        ->assertSee('Total Tagihan (Rp)');

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Filament\Resources\Payments\Pages\CreatePayment::class)
        ->assertSuccessful()
        ->assertSee('Kode Booking')
        ->assertSee('Metode Pembayaran')
        ->assertSee('Nominal Pembayaran (Rp)');
});

// 16. Filament Admin List Pages Render Nullable Date Columns Correctly
test('filament admin list payments and waitlists render without carbon invalid format exception on null dates', function () {
    $admin = User::where('role', 'admin')->first();
    $session = GameSession::first();
    $customer = User::where('role', 'customer')->first();

    $booking = Booking::create([
        'booking_code' => 'BOOK-TEST-PAY-01',
        'game_session_id' => $session->id,
        'user_id' => $customer->id,
        'booking_type' => 'individual',
        'player_count' => 1,
        'price_per_player' => 25000,
        'total_amount' => 25000,
        'status' => 'pending_payment',
    ]);

    // Create a payment with null verified_at (pending verification)
    $payment = Payment::create([
        'booking_id' => $booking->id,
        'payment_method' => 'bank_transfer',
        'amount' => 25000,
        'status' => 'pending_verification',
        'verified_at' => null,
        'verified_by' => null,
        'reference_number' => null,
    ]);

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Filament\Resources\Payments\Pages\ListPayments::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$payment]);

    // Create a waitlist with null offer_expires_at
    $waitlist = \App\Models\Waitlist::create([
        'game_session_id' => $session->id,
        'player_name' => 'ZeroCarbonTest',
        'player_phone' => '081299998888',
        'queue_number' => 99,
        'status' => 'waiting',
        'offer_expires_at' => null,
    ]);

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Filament\Resources\Waitlists\Pages\ListWaitlists::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$waitlist]);
});


