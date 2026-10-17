<?php

use App\Livewire\BookingForm;
use App\Models\GameSession;
use App\Models\PlayerProfile;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed');

    $this->session = GameSession::create([
        'session_code' => 'SESS-TEST-WA',
        'date' => Carbon::tomorrow()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '14:30:00',
        'duration_minutes' => 30,
        'status' => 'open',
        'price_per_player' => 25000,
        'max_players' => 8,
    ]);

    Team::create([
        'game_session_id' => $this->session->id,
        'name' => 'Tim Merah',
        'color' => 'red',
        'color_code' => '#EF4444',
        'max_players' => 4,
    ]);
    Team::create([
        'game_session_id' => $this->session->id,
        'name' => 'Tim Biru',
        'color' => 'blue',
        'color_code' => '#3B82F6',
        'max_players' => 4,
    ]);
});

test('unauthenticated visitor cannot see order form and sees login gate', function () {
    Livewire::test(BookingForm::class, ['session' => $this->session])
        ->assertSee('Masuk dengan Akun Google')
        ->assertSee('Autentikasi Pemain Diperlukan')
        ->assertDontSee('Pilih Jenis Booking')
        ->assertDontSee('Kunci Slot');
});

test('google user without whatsapp phone number cannot see order form and sees whatsapp gate', function () {
    $googleUser = User::create([
        'name' => 'Agent Neo',
        'email' => 'neo@matrix.io',
        'google_id' => 'google_uid_123456',
        'avatar' => 'https://lh3.googleusercontent.com/a/test',
        'phone' => null,
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($googleUser);

    Livewire::test(BookingForm::class, ['session' => $this->session])
        ->assertSee('Verifikasi WhatsApp Pemain')
        ->assertSee('Wajib Mengisi Nomor WhatsApp')
        ->assertSee('Simpan WhatsApp')
        ->assertDontSee('Pilih Jenis Booking')
        ->assertDontSee('Kunci Slot');
});

test('google user without phone cannot submit booking directly', function () {
    $googleUser = User::create([
        'name' => 'Agent Trinity',
        'email' => 'trinity@matrix.io',
        'google_id' => 'google_uid_789012',
        'phone' => '',
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($googleUser);

    Livewire::test(BookingForm::class, ['session' => $this->session])
        ->call('submitBooking')
        ->assertSee('Wajib mengisi nomor WhatsApp aktif terlebih dahulu');
});

test('google user can save whatsapp number in-place to immediately unlock order form', function () {
    $googleUser = User::create([
        'name' => 'Agent Morpheus',
        'email' => 'morpheus@matrix.io',
        'google_id' => 'google_uid_345678',
        'phone' => null,
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($googleUser);

    Livewire::test(BookingForm::class, ['session' => $this->session])
        // Attempt with invalid phone format
        ->set('whatsappModalInput', '12345')
        ->call('saveWhatsApp')
        ->assertHasErrors(['whatsappModalInput' => 'regex'])
        ->assertSee('Wajib Mengisi Nomor WhatsApp')
        // Now set valid WhatsApp number
        ->set('whatsappModalInput', '081299887766')
        ->call('saveWhatsApp')
        ->assertHasNoErrors()
        // Form is now unlocked!
        ->assertSee('Pilih Jenis Booking')
        ->assertSee('Kunci Slot')
        ->assertDontSee('Wajib Mengisi Nomor WhatsApp');

    expect($googleUser->fresh()->phone)->toBe('081299887766');

    $profile = PlayerProfile::where('user_id', $googleUser->id)->first();
    expect($profile)->not->toBeNull()
        ->and($profile->phone)->toBe('081299887766');
});

test('user updating phone on profile page with redirect_to returns to booking order form', function () {
    $googleUser = User::create([
        'name' => 'Agent Cypher',
        'email' => 'cypher@matrix.io',
        'google_id' => 'google_uid_998877',
        'phone' => null,
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($googleUser);

    $bookingUrl = route('booking.show', $this->session);

    $response = $this->post(route('profile.update'), [
        'name' => 'Agent Cypher Updated',
        'phone' => '087711223344',
        'redirect_to' => $bookingUrl,
    ]);

    $response->assertRedirect($bookingUrl);
    expect($googleUser->fresh()->phone)->toBe('087711223344');

    // Re-authenticate with refreshed user model so Livewire has updated phone
    $this->actingAs($googleUser->fresh());

    // Visiting booking form now displays full order form
    Livewire::test(BookingForm::class, ['session' => $this->session])
        ->assertSee('Pilih Jenis Booking')
        ->assertSee('Kunci Slot')
        ->assertDontSee('Wajib Mengisi Nomor WhatsApp');
});
