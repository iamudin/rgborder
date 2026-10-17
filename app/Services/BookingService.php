<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameSession;
use App\Models\Payment;
use App\Models\PlayerProfile;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        protected TeamAssignmentService $teamAssignmentService,
        protected SessionService $sessionService,
        protected WaitlistService $waitlistService
    ) {}

    /**
     * Create a customer booking with atomic locking and capacity check.
     *
     * @param User $user
     * @param int $sessionId
     * @param string $bookingType 'individual', 'group', 'full_team'
     * @param array $playersData Array of ['name' => ..., 'phone' => ...]
     * @param int|null $targetTeamId
     * @param string $paymentMethod 'qris', 'bank_transfer', 'cash_on_site'
     * @param string|null $notes
     * @return Booking
     * @throws ValidationException
     */
    public function createCustomerBooking(
        User $user,
        int $sessionId,
        string $bookingType,
        array $playersData,
        ?int $targetTeamId = null,
        string $paymentMethod = 'qris',
        ?string $notes = null
    ): Booking {
        $playerCount = count($playersData);

        if ($playerCount < 1) {
            throw ValidationException::withMessages([
                'players' => 'Minimal harus ada 1 pemain yang didaftarkan.',
            ]);
        }

        if ($bookingType === 'individual' && $playerCount !== 1) {
            throw ValidationException::withMessages([
                'players' => 'Booking individual hanya untuk 1 pemain.',
            ]);
        }

        if ($bookingType === 'full_team' && $playerCount !== 4) {
            throw ValidationException::withMessages([
                'players' => 'Booking tim penuh harus mendaftarkan tepat 4 pemain.',
            ]);
        }

        if ($bookingType === 'group' && ($playerCount < 2 || $playerCount > 4)) {
            throw ValidationException::withMessages([
                'players' => 'Booking kelompok harus beranggotakan 2 hingga 4 pemain.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $sessionId,
            $bookingType,
            $playersData,
            $targetTeamId,
            $paymentMethod,
            $notes,
            $playerCount
        ) {
            // Lock session row for atomic check
            /** @var GameSession $session */
            $session = GameSession::where('id', $sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $session->canBeBooked()) {
                throw ValidationException::withMessages([
                    'session' => 'Sesi permainan ini sudah tidak dapat dipesan (penuh, sudah dimulai, atau ditutup).',
                ]);
            }

            // Check overall session capacity
            if ($session->available_slots_count < $playerCount) {
                throw ValidationException::withMessages([
                    'session' => "Slot tidak mencukupi. Sesi ini hanya menyisakan {$session->available_slots_count} slot kosong.",
                ]);
            }

            // Check double booking for any player phone within this session
            $phones = collect($playersData)->pluck('phone')->filter()->toArray();
            $existingBookingForPlayer = BookingPlayer::whereHas('booking', function ($q) use ($session) {
                $q->where('game_session_id', $session->id)
                    ->whereIn('status', ['confirmed', 'pending_payment']);
            })->whereIn('player_phone', $phones)->first();

            if ($existingBookingForPlayer) {
                throw ValidationException::withMessages([
                    'players' => "Pemain dengan nomor WhatsApp {$existingBookingForPlayer->player_phone} ({$existingBookingForPlayer->player_name}) sudah terdaftar pada sesi ini.",
                ]);
            }

            // Resolve team assignment (guarantees entire group in SAME team)
            $assignedTeam = $this->teamAssignmentService->resolveTeamForBooking(
                $session,
                $playerCount,
                $targetTeamId
            );

            // Price snapshot calculation
            $pricePerPlayer = (int) $session->price_per_player;
            $totalAmount = $pricePerPlayer * $playerCount;

            $holdMinutes = (int) Setting::get('hold_duration_minutes', 10);
            $expiresAt = Carbon::now()->addMinutes($holdMinutes);

            // Generate unique booking code
            $bookingCode = 'RGB-'.strtoupper(Str::random(6));
            while (Booking::where('booking_code', $bookingCode)->exists()) {
                $bookingCode = 'RGB-'.strtoupper(Str::random(6));
            }

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $user->id,
                'game_session_id' => $session->id,
                'booking_type' => $bookingType,
                'target_team_id' => $assignedTeam->id,
                'total_players' => $playerCount,
                'price_per_player' => $pricePerPlayer,
                'total_amount' => $totalAmount,
                'status' => 'pending_payment',
                'expires_at' => $expiresAt,
                'notes' => $notes,
            ]);

            // Create Player Profiles and Booking Players
            foreach ($playersData as $index => $pData) {
                $pName = trim($pData['name']);
                $pPhone = trim($pData['phone']);

                // If first player is the logged-in user
                $isPrimaryUser = ($index === 0 && ($pPhone === $user->phone || empty($pPhone)));
                if ($isPrimaryUser && ! empty($user->phone)) {
                    $pPhone = $user->phone;
                }

                $profile = PlayerProfile::firstOrCreate(
                    ['phone' => $pPhone],
                    [
                        'user_id' => $isPrimaryUser ? $user->id : null,
                        'name' => $pName,
                        'email' => $isPrimaryUser ? $user->email : null,
                        'is_guest' => ! $isPrimaryUser,
                    ]
                );

                BookingPlayer::create([
                    'booking_id' => $booking->id,
                    'player_profile_id' => $profile->id,
                    'team_id' => $assignedTeam->id,
                    'player_name' => $pName,
                    'player_phone' => $pPhone,
                    'check_in_status' => 'pending',
                    'check_in_token' => 'CHK-'.Str::random(16),
                ]);
            }

            // Create initial payment record
            Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $paymentMethod,
                'amount' => $totalAmount,
                'status' => 'unpaid',
            ]);

            $this->sessionService->syncSessionStatus($session);

            ActivityLog::record('booking.created', $booking, [
                'code' => $bookingCode,
                'type' => $bookingType,
                'players' => $playerCount,
                'team' => $assignedTeam->name,
                'total' => $totalAmount,
            ], $user);

            return $booking;
        });
    }

    /**
     * Create walk-in booking by Admin without requiring Google account.
     *
     * @param GameSession $session
     * @param array $playersData
     * @param int|null $targetTeamId
     * @param string $paymentMethod
     * @param bool $isPaidDirectly
     * @param User|null $operator
     * @return Booking
     * @throws ValidationException
     */
    public function createWalkInBooking(
        GameSession $session,
        array $playersData,
        ?int $targetTeamId = null,
        string $paymentMethod = 'cash_on_site',
        bool $isPaidDirectly = true,
        ?User $operator = null
    ): Booking {
        $playerCount = count($playersData);

        if ($playerCount < 1) {
            throw ValidationException::withMessages([
                'players' => 'Minimal 1 pemain walk-in.',
            ]);
        }

        return DB::transaction(function () use (
            $session,
            $playersData,
            $targetTeamId,
            $paymentMethod,
            $isPaidDirectly,
            $operator,
            $playerCount
        ) {
            /** @var GameSession $lockedSession */
            $lockedSession = GameSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($lockedSession->available_slots_count < $playerCount) {
                throw ValidationException::withMessages([
                    'session' => "Slot tidak mencukupi (tersisa {$lockedSession->available_slots_count} slot).",
                ]);
            }

            $assignedTeam = $this->teamAssignmentService->resolveTeamForBooking(
                $lockedSession,
                $playerCount,
                $targetTeamId
            );

            $pricePerPlayer = (int) $lockedSession->price_per_player;
            $totalAmount = $pricePerPlayer * $playerCount;

            $bookingCode = 'WLK-'.strtoupper(Str::random(6));
            while (Booking::where('booking_code', $bookingCode)->exists()) {
                $bookingCode = 'WLK-'.strtoupper(Str::random(6));
            }

            $bookingType = $playerCount === 1 ? 'individual' : ($playerCount === 4 ? 'full_team' : 'group');

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => null,
                'game_session_id' => $lockedSession->id,
                'booking_type' => $bookingType,
                'target_team_id' => $assignedTeam->id,
                'total_players' => $playerCount,
                'price_per_player' => $pricePerPlayer,
                'total_amount' => $totalAmount,
                'status' => $isPaidDirectly ? 'confirmed' : 'pending_payment',
                'expires_at' => $isPaidDirectly ? null : Carbon::now()->addMinutes(15),
                'notes' => 'Pemain Walk-in di lokasi',
            ]);

            foreach ($playersData as $pData) {
                $name = trim($pData['name']);
                $phone = trim($pData['phone'] ?? '08'.rand(1000000000, 9999999999));

                $profile = PlayerProfile::firstOrCreate(
                    ['phone' => $phone],
                    ['name' => $name, 'is_guest' => true]
                );

                BookingPlayer::create([
                    'booking_id' => $booking->id,
                    'player_profile_id' => $profile->id,
                    'team_id' => $assignedTeam->id,
                    'player_name' => $name,
                    'player_phone' => $phone,
                    'check_in_status' => 'pending',
                    'check_in_token' => 'CHK-'.Str::random(16),
                ]);
            }

            Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $paymentMethod,
                'amount' => $totalAmount,
                'status' => $isPaidDirectly ? 'paid' : 'unpaid',
                'verified_at' => $isPaidDirectly ? Carbon::now() : null,
                'verified_by' => $isPaidDirectly ? $operator?->id : null,
                'reference_number' => 'WALKIN-'.Str::random(8),
            ]);

            $this->sessionService->syncSessionStatus($lockedSession);

            ActivityLog::record('booking.walkin_created', $booking, [
                'code' => $bookingCode,
                'players' => $playerCount,
                'team' => $assignedTeam->name,
            ], $operator);

            return $booking;
        });
    }

    /**
     * Cancel a booking and notify waitlist.
     *
     * @param Booking $booking
     * @param string|null $reason
     * @param User|null $operator
     * @return bool
     */
    public function cancelBooking(Booking $booking, ?string $reason = null, ?User $operator = null): bool
    {
        if (in_array($booking->status, ['cancelled', 'expired'])) {
            return false;
        }

        DB::transaction(function () use ($booking, $reason, $operator) {
            $booking->update([
                'status' => 'cancelled',
                'notes' => trim(($booking->notes ?? '')."\nDibatalkan: ".($reason ?? 'Permintaan pengguna/admin')),
            ]);

            // Release payment if unpaid/pending
            $booking->payments()->whereIn('status', ['unpaid', 'pending_verification'])->update([
                'status' => 'failed',
                'notes' => 'Booking dibatalkan',
            ]);

            $session = $booking->gameSession;
            $this->sessionService->syncSessionStatus($session);

            // Check if waitlist penders can now be offered slots!
            $this->waitlistService->checkAndOfferAvailableSlots($session);

            ActivityLog::record('booking.cancelled', $booking, [
                'code' => $booking->booking_code,
                'reason' => $reason,
            ], $operator);
        });

        return true;
    }

    /**
     * Expire hold reservations whose hold timeout elapsed.
     *
     * @return int Count of expired bookings
     */
    public function expireStaleHolds(): int
    {
        $expiredBookings = Booking::where('status', 'pending_payment')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'expired',
                    'notes' => trim(($booking->notes ?? '')."\nKedaluwarsa karena melewati batas waktu pembayaran."),
                ]);

                $booking->payments()->whereIn('status', ['unpaid', 'pending_verification'])->update([
                    'status' => 'expired',
                ]);

                $session = $booking->gameSession;
                $this->sessionService->syncSessionStatus($session);
                $this->waitlistService->checkAndOfferAvailableSlots($session);

                ActivityLog::record('booking.expired', $booking, [
                    'code' => $booking->booking_code,
                ]);
            });

            $count++;
        }

        return $count;
    }
}
